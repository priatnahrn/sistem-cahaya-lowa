<?php

namespace App\Http\Controllers;

use App\Models\LogActivity;
use App\Models\TagihanPenjualan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TagihanPenjualanController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // ✅ Check permission view
        $this->authorize('tagihan_penjualan.view');

        // ✅ Filter hanya yang belum lunas
        $tagihans = TagihanPenjualan::with('penjualan.pelanggan')
            ->belumLunas()
            ->latest()
            ->get();

        return view('auth.kasir.tagihan-penjualan.index', compact('tagihans'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // ✅ Check permission view
        $this->authorize('tagihan_penjualan.view');

        $tagihan = TagihanPenjualan::with([
            'penjualan.pelanggan',
            'penjualan.items.item',
            'penjualan.items.gudang',
            'penjualan.items.satuan',
        ])->findOrFail($id);

        return view('auth.kasir.tagihan-penjualan.show', compact('tagihan'));
    }

    /**
     * Show the form for editing (halaman bayar)
     */
    public function edit(string $id)
    {
        // ✅ Check permission update
        $this->authorize('tagihan_penjualan.update');

        $tagihan = TagihanPenjualan::with([
            'penjualan.pelanggan',
            'penjualan.items.item',
            'penjualan.items.gudang',
            'penjualan.items.satuan',
        ])->findOrFail($id);

        // Cek apakah sudah lunas
        if ($tagihan->is_lunas) {
            return redirect()
                ->route('tagihan-penjualan.show', $id)
                ->with('info', 'Tagihan sudah lunas.');
        }

        return view('auth.kasir.tagihan-penjualan.show', compact('tagihan'));
    }

    /**
     * Update the specified resource in storage (Proses Pembayaran)
     */
    public function update(Request $request, string $id)
    {
        // ✅ Check permission update
        $this->authorize('tagihan_penjualan.update');

        // ✅ Validasi input (TIDAK ADA catatan manual)
        $request->validate([
            'jumlah_bayar_tambahan' => 'required|numeric|min:1',
            'metode' => 'required|in:cash,transfer',
            'bank' => 'nullable|string|max:100',
        ]);

        try {
            DB::beginTransaction();

            $tagihan = TagihanPenjualan::findOrFail($id);
            $jumlahBayarTambahan = $request->jumlah_bayar_tambahan;

            // Cek apakah sudah lunas
            if ($tagihan->is_lunas) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tagihan sudah lunas.'
                    ], 400);
                }

                return back()->with('error', 'Tagihan sudah lunas.');
            }

            // Validasi: jumlah bayar tidak boleh melebihi sisa
            if ($jumlahBayarTambahan > $tagihan->sisa) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Jumlah pembayaran melebihi sisa tagihan.'
                    ], 400);
                }

                return back()
                    ->with('error', 'Jumlah pembayaran melebihi sisa tagihan.')
                    ->withInput();
            }

            // ✅ Generate catatan OTOMATIS (seperti TagihanPembelian)
            $metode = $request->metode ?? 'cash';
            $bank = $request->bank;

            $catatanPembayaran = "Pembayaran " .
                ($metode === 'cash' ? 'tunai' : 'transfer');

            if ($metode === 'transfer' && $bank) {
                $catatanPembayaran .= " via {$bank}";
            }

            $catatanPembayaran .= " sebesar Rp " .
                number_format($jumlahBayarTambahan, 0, ',', '.') .
                " pada " . now()->timezone('Asia/Makassar')->format('d/m/Y H:i');

            // Proses pembayaran menggunakan method bayar dari model
            $success = $tagihan->bayar($jumlahBayarTambahan, $catatanPembayaran);

            if (!$success) {
                throw new \Exception('Gagal memproses pembayaran');
            }

            // Update updated_by
            $tagihan->updated_by = Auth::id();
            $tagihan->save();

            // Log activity
            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'bayar_tagihan_penjualan',
                'description'   => "Pembayaran tagihan {$tagihan->no_tagihan} sebesar Rp " .
                    number_format($jumlahBayarTambahan, 0, ',', '.'),
                'ip_address'    => $request->ip(),
                'user_agent'    => $request->userAgent(),
            ]);

            DB::commit();

            // Reload untuk mendapatkan data terbaru
            $tagihan->refresh();

            $message = $tagihan->is_lunas
                ? "Pembayaran berhasil! Tagihan telah lunas."
                : "Pembayaran cicilan berhasil dicatat. Sisa: Rp " . number_format($tagihan->sisa, 0, ',', '.');

            // Return JSON jika AJAX request
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => [
                        'jumlah_bayar' => $tagihan->jumlah_bayar,
                        'sisa' => $tagihan->sisa,
                        'is_lunas' => $tagihan->is_lunas,
                        'persentase' => $tagihan->persentase_bayar
                    ]
                ]);
            }

            return redirect()
                ->route('tagihan-penjualan.show', $id)
                ->with('success', $message);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak valid.',
                    'errors' => $e->errors()
                ], 422);
            }

            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating tagihan pembayaran: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat memproses pembayaran: ' . $e->getMessage()
                ], 500);
            }

            return back()
                ->with('error', 'Terjadi kesalahan saat memproses pembayaran.')
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // ✅ Check permission delete
        $this->authorize('tagihan_penjualan.delete');

        try {
            DB::beginTransaction();

            $tagihan = TagihanPenjualan::findOrFail($id);

            // Cek apakah tagihan sudah ada pembayaran
            if ($tagihan->jumlah_bayar > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tagihan yang sudah ada pembayaran tidak dapat dihapus.'
                ], 400);
            }

            $noTagihan = $tagihan->no_tagihan;

            // Hapus tagihan
            $tagihan->delete();

            DB::commit();

            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'delete_tagihan_penjualan',
                'description'   => "Deleted tagihan penjualan: {$noTagihan}",
                'ip_address'    => request()->ip(),
                'user_agent'    => request()->userAgent(),
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Tagihan {$noTagihan} berhasil dihapus."
                ]);
            }

            return redirect()
                ->route('tagihan-penjualan.index')
                ->with('success', "Tagihan {$noTagihan} berhasil dihapus.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting tagihan penjualan: ' . $e->getMessage());

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat menghapus tagihan.'
                ], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'Terjadi kesalahan saat menghapus tagihan.');
        }
    }
}
