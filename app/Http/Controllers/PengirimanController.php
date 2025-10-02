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

        return view('auth.penjualan.pengiriman.index', compact('pengirimans'));
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
            'tanggal_kirim' => 'nullable|date',
            'alamat'        => 'nullable|string|max:255',
            'status'        => 'required|in:pending,on_process,delivered,cancelled',
        ]);

        try {
            $pengiriman->update([
                'tanggal_kirim' => $data['tanggal_kirim'] ?? $pengiriman->tanggal_kirim,
                'alamat'        => $data['alamat'] ?? $pengiriman->alamat,
                'status'        => $data['status'],
            ]);

            return redirect()->back()->with('success', 'Pengiriman berhasil diperbarui');
        } catch (\Throwable $e) {
            Log::error('Pengiriman update error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal memperbarui pengiriman');
        }
    }
}
