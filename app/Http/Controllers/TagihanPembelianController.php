<?php

namespace App\Http\Controllers;

use App\Models\LogActivity;
use App\Models\TagihanPembelian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TagihanPembelianController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // ✅ Check permission view
        $this->authorize('tagihan_pembelian.view');

        $tagihans = TagihanPembelian::with('pembelian.supplier')
            ->latest()
            ->get();

        return view('auth.pembelian.tagihan.index', compact('tagihans'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // ✅ Check permission view
        $this->authorize('tagihan_pembelian.view');

        $tagihan = TagihanPembelian::with([
            'pembelian.supplier',
            'pembelian.items.item',
            'pembelian.items.gudang',
            'pembelian.items.satuan',
        ])->findOrFail($id);

        return view('auth.pembelian.tagihan.show', compact('tagihan'));
    }

    /**
     * Show the form for editing (update pembayaran)
     */
    public function edit(string $id)
    {
        // ✅ Check permission update
        $this->authorize('tagihan_pembelian.update');

        $tagihan = TagihanPembelian::with([
            'pembelian.supplier',
            'pembelian.items.item',
            'pembelian.items.gudang',
            'pembelian.items.satuan',
        ])->findOrFail($id);

        // Cek apakah sudah lunas
        if ($tagihan->is_lunas) {
            return redirect()
                ->route('tagihan-pembelian.show', $id)
                ->with('info', 'Tagihan sudah lunas.');
        }

        return view('auth.pembelian.tagihan.show', compact('tagihan'));
    }

    /**
     * Update pembayaran tagihan (untuk cicilan/pelunasan)
     */
    public function update(Request $request, string $id)
    {
        // ✅ Check permission update
        $this->authorize('tagihan_pembelian.update');

        $request->validate([
            'jumlah_bayar_tambahan' => 'required|numeric|min:1',
            'metode' => 'nullable|in:cash,transfer',
            'bank' => 'nullable|string|max:100',
            'catatan' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $tagihan = TagihanPembelian::findOrFail($id);
            $jumlahBayarTambahan = $request->jumlah_bayar_tambahan;

            // Validasi: jumlah bayar tambahan tidak boleh melebihi sisa
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

            // Generate catatan otomatis jika tidak ada
            $metode = $request->metode ?? 'cash';
            $bank = $request->bank;
            
            $catatanPembayaran = $request->catatan;
            if (empty($catatanPembayaran)) {
                $catatanPembayaran = "Pembayaran " . 
                    ($metode === 'cash' ? 'tunai' : 'transfer');
                
                if ($metode === 'transfer' && $bank) {
                    $catatanPembayaran .= " via {$bank}";
                }
                
                $catatanPembayaran .= " sebesar Rp " . 
                    number_format($jumlahBayarTambahan, 0, ',', '.') . 
                    " pada " . now()->format('d/m/Y H:i');
            }

            // Proses pembayaran menggunakan method dari model
            $success = $tagihan->bayar($jumlahBayarTambahan, $catatanPembayaran);

            if (!$success) {
                throw new \Exception('Gagal memproses pembayaran');
            }

            // Set user yang update
            $tagihan->updated_by = Auth::id();
            $tagihan->save();

            // Update status pembelian jika tagihan sudah lunas
            if ($tagihan->is_lunas) {
                $tagihan->pembelian->status = 'paid';
                $tagihan->pembelian->save();
            }

            DB::commit();

            $message = $tagihan->is_lunas
                ? "Pembayaran berhasil! Tagihan telah lunas."
                : "Pembayaran cicilan berhasil dicatat. Sisa: Rp " . number_format($tagihan->sisa, 0, ',', '.');

            // Return JSON jika AJAX request
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => [
                        'sisa' => $tagihan->sisa,
                        'jumlah_bayar' => $tagihan->jumlah_bayar,
                        'is_lunas' => $tagihan->is_lunas,
                        'persentase_bayar' => $tagihan->persentase_bayar
                    ]
                ]);
            }

            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'update_tagihan_pembelian',
                'description'   => "Updated pembayaran tagihan ID: {$id}, tambahan bayar: Rp " . number_format($jumlahBayarTambahan, 0, ',', '.'),
                'ip_address'    => $request->ip(),
                'user_agent'    => $request->userAgent(),
            ]);

            return redirect()
                ->route('tagihan-pembelian.show', $id)
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating tagihan: ' . $e->getMessage());

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
        $this->authorize('tagihan_pembelian.delete');

        try {
            DB::beginTransaction();

            $tagihan = TagihanPembelian::findOrFail($id);

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

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Tagihan {$noTagihan} berhasil dihapus."
                ]);
            }

            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'delete_tagihan_pembelian',
                'description'   => "Deleted tagihan pembelian: {$noTagihan}",
                'ip_address'    => request()->ip(),
                'user_agent'    => request()->userAgent(),
            ]);

            return redirect()
                ->route('tagihan-pembelian.index')
                ->with('success', "Tagihan {$noTagihan} berhasil dihapus.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting tagihan: ' . $e->getMessage());

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