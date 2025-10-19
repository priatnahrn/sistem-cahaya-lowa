<?php

namespace App\Http\Controllers;

use App\Models\LogActivity;
use App\Models\TagihanPenjualan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

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

        $tagihans = TagihanPenjualan::with('penjualan.pelanggan')
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
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // ✅ Check permission delete
        $this->authorize('tagihan_penjualan.delete');

        try {
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