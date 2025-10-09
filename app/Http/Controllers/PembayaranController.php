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
            'jumlah_bayar' => 'required|numeric|min:1',
            'method' => 'required|in:cash,transfer,qris,wallet',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $penjualan = Penjualan::findOrFail($request->penjualan_id);

        // Hitung total pembayaran sebelumnya
        $totalSebelumnya = Pembayaran::where('penjualan_id', $penjualan->id)->sum('jumlah_bayar');

        // Batasi agar tidak melebihi total penjualan
        $jumlahBayar = min($request->jumlah_bayar, $penjualan->total - $totalSebelumnya);

        $totalSekarang = $totalSebelumnya + $jumlahBayar;
        $sisa = max(0, $penjualan->total - $totalSekarang);
        $isLunas = $totalSekarang >= $penjualan->total;

        // Simpan pembayaran
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

        // Update sisa semua pembayaran terkait
        Pembayaran::where('penjualan_id', $penjualan->id)->update(['sisa' => $sisa]);

        // Update status penjualan & tagihan
        $penjualan->update([
            'status_bayar' => $isLunas ? 'paid' : 'unpaid',
        ]);

        if ($tagihan = TagihanPenjualan::where('penjualan_id', $penjualan->id)->first()) {
            $tagihan->update([
                'status_tagihan' => $isLunas ? 'lunas' : 'belum_lunas',
                'sisa' => $sisa,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran berhasil disimpan dan status disinkronkan.',
            'data' => $pembayaran,
        ]);
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
