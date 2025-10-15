<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\Penjualan;
use App\Models\TagihanPenjualan;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PembayaranController extends Controller
{
    /**
     * Tampilkan daftar pembayaran.
     */
    public function index(Request $request)
    {


        // Ambil semua pembayaran dengan relasi penjualan (jika ada)
        $pembayarans = Pembayaran::with(['penjualan.pelanggan'])
            ->latest('tanggal')
            ->get();

        return view('auth.kasir.pembayaran.index', compact('pembayarans'));
    }

    /**
     * (Opsional) Ekspor data ke CSV sederhana.
     */
    protected function export()
    {
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
     * Tampilkan form tambah pembayaran.
     */
    public function create()
    {
        return view('auth.kasir.pembayaran.create');
    }

    /**
     * Simpan data pembayaran baru.
     */
    public function store(Request $request)
    {
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

            // ✅ FIX: Hitung HANYA pembayaran positif (exclude pengembalian)
            $totalPembayaranSebelumnya = Pembayaran::where('penjualan_id', $penjualan->id)
                ->where('jumlah_bayar', '>', 0) // ✅ HANYA pembayaran, BUKAN pengembalian
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
                    // ✅ TOTAL NAIK → Perlu pembayaran tambahan
                    $jumlahBayar = $request->jumlah_bayar;

                    if ($jumlahBayar < $adjustmentAmount) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Nominal pembayaran kurang dari kekurangan yang harus dibayar.',
                        ], 400);
                    }

                    // ✅ HITUNG TOTAL SETELAH PEMBAYARAN TAMBAHAN
                    $totalSekarang = $totalPembayaranSebelumnya + $jumlahBayar;

                    // ✅ HITUNG SISA
                    $sisa = max(0, $penjualan->total - $totalSekarang);

                    // ✅ CEK LUNAS
                    $isLunas = $sisa == 0;

                    Log::info('💰 Perhitungan Adjustment (Total Naik):', [
                        'total_pembayaran_sebelumnya' => $totalPembayaranSebelumnya,
                        'jumlah_bayar_tambahan' => $jumlahBayar,
                        'total_sekarang' => $totalSekarang,
                        'total_penjualan' => $penjualan->total,
                        'sisa' => $sisa,
                        'is_lunas' => $isLunas,
                    ]);

                    // Simpan pembayaran tambahan
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

                    // ✅ Update sisa SEMUA pembayaran terkait
                    Pembayaran::where('penjualan_id', $penjualan->id)->update(['sisa' => $sisa]);

                    // ✅ Update status penjualan
                    $penjualan->update([
                        'status_bayar' => $isLunas ? 'paid' : 'unpaid',
                    ]);

                    Log::info('✅ Status Penjualan Updated:', [
                        'status_bayar' => $penjualan->status_bayar,
                        'sisa' => $sisa,
                    ]);

                    // ✅ Update tagihan jika ada
                    if ($tagihan = TagihanPenjualan::where('penjualan_id', $penjualan->id)->first()) {
                        $tagihan->update([
                            'status_tagihan' => $isLunas ? 'lunas' : 'belum_lunas',
                            'sisa' => $sisa,
                        ]);
                    }
                } elseif ($adjustmentAmount < 0) {
                    // ✅ TOTAL TURUN → Ada pengembalian dana
                    $pengembalian = abs($adjustmentAmount);

                    // Catat sebagai pembayaran negatif (pengembalian)
                    $pembayaran = Pembayaran::create([
                        'penjualan_id' => $penjualan->id,
                        'tanggal' => now(),
                        'jumlah_bayar' => -$pengembalian, // ✅ Nilai negatif untuk pengembalian
                        'sisa' => 0,
                        'method' => 'cash',
                        'keterangan' => $request->keterangan ?? "Pengembalian dana karena pengurangan total transaksi (kelebihan bayar Rp " . number_format($pengembalian, 0, ',', '.') . ")",
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);

                    // ✅ Update sisa semua pembayaran (tetap 0 karena sudah lunas)
                    Pembayaran::where('penjualan_id', $penjualan->id)->update(['sisa' => 0]);

                    // ✅ Status PASTI LUNAS
                    $penjualan->update([
                        'status_bayar' => 'paid',
                    ]);

                    // ✅ Update tagihan jika ada
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

                // ✅ FIX: Gunakan total pembayaran yang sudah exclude pengembalian
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

                Pembayaran::where('penjualan_id', $penjualan->id)->update(['sisa' => $sisa]);

                $penjualan->update([
                    'status_bayar' => $isLunas ? 'paid' : 'unpaid',
                ]);

                if ($tagihan = TagihanPenjualan::where('penjualan_id', $penjualan->id)->first()) {
                    $tagihan->update([
                        'status_tagihan' => $isLunas ? 'lunas' : 'belum_lunas',
                        'sisa' => $sisa,
                    ]);
                }
            }

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
     * Hapus data pembayaran.
     */
    public function destroy($id)
    {
        $pembayaran = Pembayaran::findOrFail($id);
        $pembayaran->delete();

        return response()->json(['message' => 'Pembayaran berhasil dihapus.']);
    }
}
