<?php

namespace App\Http\Controllers;

use App\Models\KasKeuangan; // ✅ TAMBAHAN IMPORT
use App\Models\LogActivity;
use App\Models\Pembayaran;
use App\Models\Penjualan;
use App\Models\TagihanPenjualan;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PembayaranController extends Controller
{
    use AuthorizesRequests;

    /**
     * Tampilkan daftar pembayaran.
     */
    public function index(Request $request)
    {
        $this->authorize('pembayaran.view');

        $query = Pembayaran::with(['penjualan.pelanggan', 'createdBy']) // ✅ Tambahkan 'creator'
            ->latest('tanggal');

        $user = Auth::user();

        // ✅ Filter berdasarkan role
        if (!$user->hasRole(['super-admin', 'Kasir'])) { // atau cek role_id
            $query->where('created_by', $user->id);
        }

        $pembayarans = $query->get();

        return view('auth.kasir.pembayaran.index', compact('pembayarans'));
    }

    /**
     * Ekspor data ke CSV sederhana.
     */
    protected function export()
    {
        $this->authorize('pembayaran.view');

        $fileName = 'pembayaran_' . now()->format('Ymd_His') . '.csv';
        $pembayarans = Pembayaran::with('penjualan')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
        ];

        $columns = ['No Transaksi', 'Tanggal', 'No Faktur Penjualan', 'Total Bayar', 'Status'];

        $callback = function () use ($pembayarans, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($pembayarans as $p) {
                fputcsv($file, [
                    $p->no_transaksi,
                    optional($p->tanggal ? Carbon::parse($p->tanggal) : null)?->format('Y-m-d'),
                    optional($p->penjualan)->no_faktur ?? '-',
                    number_format($p->jumlah_bayar, 0, ',', '.'),
                    strtoupper($p->status),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Tampilkan detail pembayaran.
     */
    public function show($id)
    {
        $pembayaran = Pembayaran::with(['penjualan'])->findOrFail($id);
        return view('auth.kasir.pembayaran.show', compact('pembayaran'));
    }

    /**
     * Simpan data pembayaran baru.
     * ✅ DENGAN AUTO CREATE KAS KEUANGAN
     */
    public function store(Request $request)
    {
        $this->authorize('pembayaran.create');

        $request->validate([
            'penjualan_id' => 'required|exists:penjualans,id',
            'jumlah_bayar' => 'required|numeric',
            'method' => 'required|in:cash,transfer,qris,wallet',
            'keterangan' => 'nullable|string|max:255',
            'is_adjustment' => 'nullable|boolean',
            'adjustment_amount' => 'nullable|numeric',
        ]);

        DB::beginTransaction();

        try {
            $penjualan = Penjualan::findOrFail($request->penjualan_id);
            $isAdjustment = $request->boolean('is_adjustment', false);
            $adjustmentAmount = $request->input('adjustment_amount', 0);

            // Hitung HANYA pembayaran positif (exclude pengembalian)
            $totalPembayaranSebelumnya = Pembayaran::where('penjualan_id', $penjualan->id)
                ->where('jumlah_bayar', '>', 0)
                ->sum('jumlah_bayar');

            Log::info('📊 Debug Pembayaran:', [
                'penjualan_id' => $penjualan->id,
                'total_penjualan' => $penjualan->total,
                'total_pembayaran_sebelumnya' => $totalPembayaranSebelumnya,
                'is_adjustment' => $isAdjustment,
                'adjustment_amount' => $adjustmentAmount,
                'jumlah_bayar_request' => $request->jumlah_bayar,
            ]);

            if ($isAdjustment) {
                // === ADJUSTMENT LOGIC ===
                if ($adjustmentAmount > 0) {
                    // Kekurangan → customer bayar tambahan
                    $jumlahBayar = $request->jumlah_bayar;

                    if ($jumlahBayar < $adjustmentAmount) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Nominal pembayaran kurang dari kekurangan yang harus dibayar.',
                        ], 400);
                    }

                    $totalSekarang = $totalPembayaranSebelumnya + $jumlahBayar;
                    $sisa = max(0, $penjualan->total - $totalSekarang);
                    $isLunas = $sisa == 0;

                    $pembayaran = Pembayaran::create([
                        'penjualan_id' => $penjualan->id,
                        'tanggal' => now(),
                        'jumlah_bayar' => $jumlahBayar,
                        'sisa' => $sisa,
                        'method' => $request->method,
                        'keterangan' => $request->keterangan ?? "Pembayaran tambahan karena perubahan total transaksi (kekurangan Rp " . number_format($adjustmentAmount, 0, ',', '.') . ")",
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);

                    // ✅ AUTO CREATE KAS KEUANGAN - PEMASUKAN
                    KasKeuangan::create([
                        'user_id' => Auth::id(),
                        'pembayarans_id' => $pembayaran->id,
                        'penjualans_id' => $penjualan->id,
                        'jenis' => 'masuk',
                        'nominal' => $jumlahBayar, // nominal yang dibayar
                        'keterangan' => "Pembayaran adjustment {$penjualan->no_faktur} - " . $this->getMethodLabel($request->method),
                    ]);

                    Pembayaran::where('penjualan_id', $penjualan->id)->update(['sisa' => $sisa]);

                    $penjualan->update(['status_bayar' => $isLunas ? 'paid' : 'unpaid']);

                    if ($tagihan = TagihanPenjualan::where('penjualan_id', $penjualan->id)->first()) {
                        $tagihan->update([
                            'status_tagihan' => $isLunas ? 'lunas' : 'belum_lunas',
                            'sisa' => $sisa,
                        ]);
                    }
                } elseif ($adjustmentAmount < 0) {
                    // Kelebihan → pengembalian dana
                    $pengembalian = abs($adjustmentAmount);

                    $pembayaran = Pembayaran::create([
                        'penjualan_id' => $penjualan->id,
                        'tanggal' => now(),
                        'jumlah_bayar' => -$pengembalian,
                        'sisa' => 0,
                        'method' => 'cash',
                        'keterangan' => $request->keterangan ?? "Pengembalian dana karena pengurangan total transaksi (kelebihan bayar Rp " . number_format($pengembalian, 0, ',', '.') . ")",
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);

                    // ✅ AUTO CREATE KAS KEUANGAN - PENGELUARAN
                    KasKeuangan::create([
                        'user_id' => Auth::id(),
                        'pembayarans_id' => $pembayaran->id,
                        'penjualans_id' => $penjualan->id,
                        'jenis' => 'keluar',
                        'nominal' => $pengembalian,
                        'keterangan' => "Pengembalian dana {$penjualan->no_faktur} - Cash",
                    ]);

                    Pembayaran::where('penjualan_id', $penjualan->id)->update(['sisa' => 0]);

                    $penjualan->update(['status_bayar' => 'paid']);

                    if ($tagihan = TagihanPenjualan::where('penjualan_id', $penjualan->id)->first()) {
                        $tagihan->update([
                            'status_tagihan' => 'lunas',
                            'sisa' => 0,
                        ]);
                    }
                } else {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Tidak ada perubahan total yang memerlukan adjustment.',
                    ], 400);
                }
            } else {
                // === PEMBAYARAN NORMAL ===
                $jumlahBayar = min($request->jumlah_bayar, $penjualan->total - $totalPembayaranSebelumnya);
                $totalSekarang = $totalPembayaranSebelumnya + $jumlahBayar;
                $sisa = max(0, $penjualan->total - $totalSekarang);
                $isLunas = $totalSekarang >= $penjualan->total;

                $pembayaran = Pembayaran::create([
                    'penjualan_id' => $penjualan->id,
                    'tanggal' => now(),
                    'jumlah_bayar' => $jumlahBayar,
                    'sisa' => $sisa,
                    'method' => $request->method,
                    'keterangan' => $request->keterangan,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);

                // ✅ AUTO CREATE KAS KEUANGAN - PEMASUKAN
                // ⚠️ PENTING: Yang dicatat adalah jumlahBayar (sudah di-cap max ke total penjualan)
                KasKeuangan::create([
                    'user_id' => Auth::id(),
                    'pembayarans_id' => $pembayaran->id,
                    'penjualans_id' => $penjualan->id,
                    'jenis' => 'masuk',
                    'nominal' => $jumlahBayar, // ✅ ini sudah max ke total penjualan
                    'keterangan' => "Pembayaran {$penjualan->no_faktur} - " . $this->getMethodLabel($request->method),
                ]);

                Pembayaran::where('penjualan_id', $penjualan->id)->update(['sisa' => $sisa]);

                $penjualan->update(['status_bayar' => $isLunas ? 'paid' : 'unpaid']);

                if ($tagihan = TagihanPenjualan::where('penjualan_id', $penjualan->id)->first()) {
                    $tagihan->update([
                        'status_tagihan' => $isLunas ? 'lunas' : 'belum_lunas',
                        'sisa' => $sisa,
                    ]);
                }
            }

            LogActivity::create([
                'user_id' => Auth::id(),
                'activity_type' => 'create_pembayaran',
                'description' => ($isAdjustment ? 'Adjustment pembayaran' : 'Pembayaran baru') . ' untuk penjualan: ' . $penjualan->no_faktur,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $isAdjustment ?
                    'Pembayaran adjustment berhasil disimpan.' :
                    'Pembayaran berhasil disimpan dan status disinkronkan.',
                'data' => $pembayaran,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Store Pembayaran error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan pembayaran: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update pembayaran (jika ada fitur edit)
     */
    public function update(Request $request, $id)
    {
        $this->authorize('pembayaran.update');

        $pembayaran = Pembayaran::findOrFail($id);

        $request->validate([
            'tanggal' => 'required|date',
            'method' => 'required|in:cash,transfer,qris,wallet',
            'keterangan' => 'nullable|string|max:255',
        ]);

        try {
            $pembayaran->update([
                'tanggal' => $request->tanggal,
                'method' => $request->method,
                'keterangan' => $request->keterangan,
                'updated_by' => Auth::id(),
            ]);

            // ✅ UPDATE KAS KEUANGAN juga (keterangan method)
            if ($kas = KasKeuangan::where('pembayarans_id', $pembayaran->id)->first()) {
                $kas->update([
                    'keterangan' => "Pembayaran {$pembayaran->penjualan->no_faktur} - " . $this->getMethodLabel($request->method),
                ]);
            }

            LogActivity::create([
                'user_id' => Auth::id(),
                'activity_type' => 'update_pembayaran',
                'description' => 'Updated pembayaran: ' . ($pembayaran->penjualan->no_faktur ?? '-'),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pembayaran berhasil diperbarui.'
                ]);
            }

            return redirect()->route('pembayaran.index')
                ->with('success', 'Pembayaran berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('Update Pembayaran error: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memperbarui pembayaran.'
                ], 500);
            }

            return back()->withErrors(['error' => 'Gagal memperbarui pembayaran.']);
        }
    }

    /**
     * Hapus data pembayaran.
     * ✅ Kas Keuangan akan ikut terhapus (cascade)
     */
    public function destroy($id)
    {
        $this->authorize('pembayaran.delete');

        try {
            $pembayaran = Pembayaran::findOrFail($id);

            // ✅ Kas akan otomatis terhapus karena onDelete('cascade')
            $pembayaran->delete();

            LogActivity::create([
                'user_id' => Auth::id(),
                'activity_type' => 'delete_pembayaran',
                'description' => 'Deleted pembayaran: ' . ($pembayaran->penjualan->no_faktur ?? '-'),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pembayaran berhasil dihapus.'
                ]);
            }

            return redirect()->route('pembayaran.index')
                ->with('success', 'Pembayaran berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Delete Pembayaran error: ' . $e->getMessage());

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menghapus pembayaran.'
                ], 500);
            }

            return back()->withErrors(['error' => 'Gagal menghapus pembayaran.']);
        }
    }

    /**
     * Helper: Get method label
     */
    private function getMethodLabel($method)
    {
        return match ($method) {
            'cash' => 'Tunai',
            'transfer' => 'Transfer Bank',
            'qris' => 'QRIS',
            'wallet' => 'Dompet Digital',
            default => ucfirst($method),
        };
    }
}
