<?php

namespace App\Http\Controllers;

use App\Models\LogActivity;
use App\Models\Produksi;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

class ProduksiController extends Controller
{
    use AuthorizesRequests;

    /**
     * Tampilkan daftar produksi (otomatis dari penjualan kategori SPANDEX)
     */
    public function index()
    {
        // ✅ Check permission view
        $this->authorize('produksi.view');

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
        // ✅ Check permission view
        $this->authorize('produksi.view');

        $produksi = Produksi::with([
            'penjualan.pelanggan',
            'penjualan.itemPenjualans', // ✅ tambahkan relasi itemPenjualan untuk mengakses item
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
        // ✅ Check permission update
        $this->authorize('produksi.update');

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

        LogActivity::create([
            'user_id'       => Auth::id(),
            'activity_type' => 'update_produksi',
            'description'   => 'Updated produksi ID: ' . $id . ' to status: ' . $request->status,
            'ip_address'    => $request->ip(),
            'user_agent'    => $request->userAgent(),
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
        // ✅ Check permission delete
        $this->authorize('produksi.delete');

        $produksi = Produksi::findOrFail($id);
        $produksi->delete();

        LogActivity::create([
            'user_id'       => Auth::id(),
            'activity_type' => 'delete_produksi',
            'description'   => 'Deleted produksi ID: ' . $id,
            'ip_address'    => request()->ip(),
            'user_agent'    => request()->userAgent(),
        ]);

        return redirect()->route('produksi.index')->with('success', 'Data produksi berhasil dihapus.');
    }
}