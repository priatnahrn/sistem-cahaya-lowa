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
            ->get()
            ->map(function ($p) {
                $pel = $p->penjualan?->pelanggan;

                // 🕒 Format tanggal pengiriman ke Asia/Makassar
                $tanggal_pengiriman = $p->tanggal_pengiriman
                    ? \Carbon\Carbon::parse($p->tanggal_pengiriman, 'UTC')
                    ->setTimezone('Asia/Makassar')
                    ->format('d-m-Y H:i')
                    : null;

                // 🕒 Format tanggal penjualan juga (biar seragam kalau mau dipakai nanti)
                $tanggal_penjualan = $p->penjualan?->tanggal
                    ? \Carbon\Carbon::parse($p->penjualan->tanggal, 'UTC')
                    ->setTimezone('Asia/Makassar')
                    ->format('d-m-Y H:i')
                    : null;

                $statusMap = [
                    'perlu_dikirim' => 'Perlu Dikirim',
                    'dalam_pengiriman' => 'Dalam Pengiriman',
                    'diterima' => 'Diterima',
                    'dibatalkan' => 'Dibatalkan',
                ];

                return [
                    'id' => $p->id,
                    'no_faktur' => $p->penjualan?->no_faktur ?? '-',
                    'tanggal' => $tanggal_pengiriman ?? $tanggal_penjualan,
                    'pelanggan' => $pel?->nama_pelanggan ?? 'Customer',
                    'telepon' => $pel?->kontak ?? null,
                    'alamat' => $pel?->alamat ?? null,
                    'status' => $statusMap[$p->status_pengiriman] ?? '-',
                    'supir' => $p->supir ?? null,
                    'url' => route('pengiriman.show', $p->id),
                ];
            });

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
            'tanggal_pengiriman' => 'nullable|date',
            'alamat' => 'nullable|string|max:255',
            'status' => 'required|string|in:perlu_dikirim,dalam_pengiriman,diterima',
            'supir' => 'nullable|string|max:255',
        ]);

        try {
            $pengiriman->update([
                'tanggal_pengiriman' => $data['tanggal_pengiriman']
                    ?? ($data['status'] === 'dalam_pengiriman' ? now() : $pengiriman->tanggal_pengiriman),
                'alamat' => $data['alamat'] ?? $pengiriman->alamat,
                'status_pengiriman' => $data['status'],
                'supir' => $data['supir'] ?? $pengiriman->supir,
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Status pengiriman berhasil diperbarui',
                    'data' => $pengiriman,
                ]);
            }

            return redirect()->back()->with('success', 'Pengiriman berhasil diperbarui');
        } catch (\Throwable $e) {
            Log::error('Pengiriman update error: ' . $e->getMessage() . ' | Line: ' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui pengiriman',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ], 500);
        }
    }


    public function search(Request $request)
    {
        $kode = $request->query('kode');

        // 1️⃣ Cari pengiriman dulu berdasarkan no_pengiriman
        $pengiriman = Pengiriman::with(['penjualan.pelanggan'])
            ->where('no_pengiriman', $kode)
            ->orderBy('created_at', 'desc')
            ->first();

        $penjualan = null;

        if ($pengiriman) {
            $penjualan = $pengiriman->penjualan;
        } else {
            // 2️⃣ Jika belum ketemu, cari berdasarkan no_faktur di penjualan
            $penjualan = Penjualan::with(['pelanggan', 'pembayarans'])
                ->where('no_faktur', $kode)
                ->first();

            if ($penjualan) {
                $pengiriman = Pengiriman::where('penjualan_id', $penjualan->id)
                    ->orderBy('created_at', 'desc')
                    ->first();
            }
        }

        // Jika tidak ada penjualan sama sekali
        if (! $penjualan) {
            return response()->json(['message' => 'Penjualan tidak ditemukan.'], 404);
        }

        $pel = $penjualan->pelanggan;
        $dibayar = $penjualan->pembayarans?->sum('jumlah_bayar') ?? 0;

        // 🕒 Format tanggal penjualan dan pengiriman ke Makassar
        $tanggal_penjualan = $penjualan->tanggal
            ? \Carbon\Carbon::parse($penjualan->tanggal, 'UTC')
            ->setTimezone('Asia/Makassar')
            ->format('d-m-Y H:i')
            : null;

        $tanggal_pengiriman = $pengiriman?->tanggal_pengiriman
            ? \Carbon\Carbon::parse($pengiriman->tanggal_pengiriman, 'UTC')
            ->setTimezone('Asia/Makassar')
            ->format('d-m-Y H:i')
            : null;

        // 🧾 Data JSON final
        return response()->json([
            // data penjualan
            'id' => $penjualan->id,
            'no_faktur' => $penjualan->no_faktur,
            'tanggal' => $tanggal_penjualan,
            'total' => (float) $penjualan->total,
            'status_bayar' => $penjualan->status_bayar ?? null,
            'dibayar' => (float) $dibayar,
            'sisa' => (float) ($penjualan->sisa ?? max(0, $penjualan->total - $dibayar)),

            // pelanggan
            'pelanggan_id' => $pel?->id,
            'pelanggan' => $pel?->nama_pelanggan ?? '-',
            'telepon' => $pel?->kontak ?? '-',
            'alamat' => $pel?->alamat ?? '-',

            // item (optional)
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
            'tanggal_pengiriman' => $tanggal_pengiriman,
            'status_pengiriman' => $pengiriman?->status_pengiriman ?? null,
            'supir' => $pengiriman?->supir ?? null,
        ]);
    }


    /**
     * Hapus pengiriman
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $pengiriman = Pengiriman::findOrFail($id);

            // ✅ Cegah hapus jika status sudah diterima
            if ($pengiriman->status_pengiriman === 'diterima') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pengiriman yang sudah diterima tidak dapat dihapus.',
                ], 400);
            }

            // ✅ Hapus relasi turunan kalau ada (contoh: log, item pengiriman, dsb)
            // kalau model Pengiriman punya relasi ke tabel lain, hapus dulu di sini.
            // Contoh:
            // $pengiriman->detailPengiriman()->delete();

            $pengiriman->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data pengiriman berhasil dihapus.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal menghapus pengiriman: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus pengiriman.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
