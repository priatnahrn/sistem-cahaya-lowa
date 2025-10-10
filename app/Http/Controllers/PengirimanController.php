<?php

namespace App\Http\Controllers;

use App\Models\Pengiriman;
use App\Models\Penjualan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PengirimanController extends Controller
{
    /**
     * Tampilkan semua daftar pengiriman
     */
    public function index()
    {
        $pengirimans = Pengiriman::with(['penjualan.pelanggan'])
            ->orderBy('created_at', 'desc')
            ->get();
        $pelanggan = Penjualan::with('pelanggan')->get();

        return view('auth.penjualan.pengiriman.index', compact('pengirimans', 'pelanggan'));
    }

    /**
     * Tampilkan detail pengiriman tertentu
     */
    public function show($id)
    {
        $pengiriman = Pengiriman::with(['penjualan.items.item', 'penjualan.pelanggan'])
            ->findOrFail($id);

        return view('auth.penjualan.pengiriman.show', compact('pengiriman'));
    }

    /**
     * Update status atau detail pengiriman
     */
    public function update(Request $request, $id)
    {
        $pengiriman = Pengiriman::findOrFail($id);

        $data = $request->validate([
            // validasi nama field sesuai kolom di tabel pengirimen
            'tanggal_pengiriman' => 'nullable|date',
            'alamat' => 'nullable|string|max:255',
            // gunakan nilai status yang ada di DB
            'status' => 'required|string|in:perlu_dikirim,dalam_pengiriman,diterima',
            'supir' => 'nullable|string|max:255',
        ]);

        try {
            $pengiriman->update([
                'tanggal_pengiriman' => $data['tanggal_pengiriman'] ?? $pengiriman->tanggal_pengiriman,
                'alamat' => $data['alamat'] ?? $pengiriman->alamat,
                'status_pengiriman' => $data['status'], // kolom di DB = status_pengiriman
                'supir' => $data['supir'] ?? $pengiriman->supir,
            ]);

            // Jika request AJAX/fetch => kembalikan JSON
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Status pengiriman berhasil diperbarui',
                    'data' => $pengiriman,
                ]);
            }

            return redirect()->back()->with('success', 'Pengiriman berhasil diperbarui');
        } catch (\Throwable $e) {
            Log::error('Pengiriman update error: ' . $e->getMessage());

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Gagal memperbarui pengiriman'], 500);
            }

            return redirect()->back()->with('error', 'Gagal memperbarui pengiriman');
        }
    }

    public function search(Request $request)
    {
        $kode = $request->query('kode');

        // 1) Coba temukan pengiriman berdasarkan no_pengiriman (jika barcode berisi no_pengiriman)
        $pengiriman = Pengiriman::with(['penjualan.pelanggan'])
            ->where('no_pengiriman', $kode)
            ->orderBy('created_at', 'desc')
            ->first();

        $penjualan = null;

        if ($pengiriman) {
            // jika ketemu pengiriman langsung, ambil penjualan dari relasi
            $penjualan = $pengiriman->penjualan;
        } else {
            // 2) jika nggak ketemu, cari penjualan berdasarkan no_faktur
            $penjualan = Penjualan::with('pelanggan')
                ->where('no_faktur', $kode)
                ->first();

            if ($penjualan) {
                // ambil pengiriman terkait (jika ada)
                $pengiriman = Pengiriman::where('penjualan_id', $penjualan->id)
                    ->orderBy('created_at', 'desc')
                    ->first();
            }
        }

        if (! $penjualan) {
            return response()->json(['message' => 'Penjualan tidak ditemukan.'], 404);
        }

        $pel = $penjualan->pelanggan;
        $dibayar = $penjualan->pembayarans?->sum('jumlah_bayar') ?? 0;

        return response()->json([
            // data penjualan
            'id' => $penjualan->id,
            'no_faktur' => $penjualan->no_faktur,
            'tanggal' => $penjualan->tanggal?->format('Y-m-d H:i:s') ?? null,
            'total' => (float) $penjualan->total,
            'status_bayar' => $penjualan->status_bayar ?? null,
            'dibayar' => (float) $dibayar,
            'sisa' => (float) ($penjualan->sisa ?? max(0, $penjualan->total - $dibayar)),

            // pelanggan
            'pelanggan_id' => $pel?->id,
            'pelanggan' => $pel?->nama_pelanggan ?? '-',
            'telepon' => $pel?->kontak ?? '-',
            'alamat' => $pel?->alamat ?? '-',

            // items (jika butuh; kalau model items lazy, sesuaikan)
            'items' => $penjualan->items?->map(fn($it) => [
                'id' => $it->id,
                'nama_item' => $it->item?->nama_item ?? '-',
                'qty' => (float) $it->jumlah,
                'harga' => (float) $it->harga,
                'subtotal' => (float) $it->total,
            ])->values() ?? [],

            // data pengiriman (jika ada)
            'pengiriman_id' => $pengiriman?->id,
            'no_pengiriman' => $pengiriman?->no_pengiriman ?? null,
            'status_pengiriman' => $pengiriman?->status_pengiriman ?? null,
            'supir' => $pengiriman?->supir ?? null,
        ]);
    }
}
