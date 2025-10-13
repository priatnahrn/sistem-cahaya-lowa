<?php

namespace App\Http\Controllers;

use App\Models\Produksi;
use Illuminate\Http\Request;

class ProduksiController extends Controller
{
    /**
     * Tampilkan daftar produksi (otomatis dari penjualan kategori SPANDEX)
     */
    public function index()
    {
        // Ambil data produksi dengan relasi ke penjualan dan pelanggan
        $produksis = Produksi::with(['penjualan.pelanggan'])
            ->orderByDesc('created_at')
            ->get();

        return view('auth.produksi.index', compact('produksis'));
    }

    /**
     * Tampilkan detail satu produksi (berdasarkan ID)
     */
    public function show($id)
    {
        $produksi = Produksi::with([
            'penjualan.pelanggan',
            'items.item',
            'items.satuan', // ✅ tambahkan satuan biar nama satuan bisa ditampilkan
        ])->findOrFail($id);

        return view('auth.produksi.show', compact('produksi'));
    }


    /**
     * API untuk update status produksi (misalnya: pending → in_progress → completed)
     */
    public function update(Request $request, $id)
    {
        $produksi = Produksi::findOrFail($id);

        $request->validate([
            'status' => 'required|in:pending,in_progress,completed',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date',
        ]);

        $produksi->update([
            'status' => $request->status,
            'tanggal_mulai' => $request->tanggal_mulai ?? $produksi->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai ?? $produksi->tanggal_selesai,
        ]);

        return response()->json([
            'message' => 'Status produksi berhasil diperbarui.',
            'produksi' => $produksi,
        ]);
    }

    /**
     * (Opsional) Hapus data produksi — sebaiknya jarang digunakan karena ini otomatis dari penjualan.
     */
    public function destroy($id)
    {
        $produksi = Produksi::findOrFail($id);
        $produksi->delete();

        return redirect()->route('produksi.index')->with('success', 'Data produksi berhasil dihapus.');
    }
}
