<?php

namespace App\Http\Controllers;

use App\Models\LogActivity;
use App\Models\TagihanPenjualan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TagihanPenjualanController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('tagihan_penjualan.view');

        // ✅ Filter hanya yang belum lunas
        $tagihans = TagihanPenjualan::with('penjualan.pelanggan')
            ->belumLunas()
            ->latest()
            ->get();

        return view('auth.kasir.tagihan-penjualan.index', compact('tagihans'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
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
     * ❌ DIHAPUS: edit() method
     * Pembayaran sekarang harus lewat fitur Pembayaran
     */

    /**
     * ❌ DIHAPUS: update() method
     * TagihanPenjualan akan auto-update via Pembayaran model
     */

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->authorize('tagihan_penjualan.delete');

        try {
            DB::beginTransaction();

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

            DB::commit();

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
            DB::rollBack();
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