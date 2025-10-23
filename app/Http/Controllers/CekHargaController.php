<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemGudang;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CekHargaController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // ✅ Check permission view
        $this->authorize('cek_harga.view');

        return view('auth.cek-harga.index');
    }

    /**
     * 🔍 API: Search items by nama or kode (untuk dropdown suggestion)
     */
    public function searchItems(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $items = Item::where(function ($q) use ($query) {
            $q->where('nama_item', 'like', "%{$query}%")
                ->orWhere('kode_item', 'like', "%{$query}%")
                ->orWhere('barcode', 'like', "%{$query}%");
        })
            ->with(['kategori'])
            ->limit(20)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nama_item' => $item->nama_item,
                    'kode_item' => $item->kode_item,
                    'barcode' => $item->barcode,
                    'kategori' => $item->kategori?->nama_kategori ?? '-',
                ];
            });

        return response()->json($items);
    }

    /**
     * 📦 API: Get detail item dengan semua harga & stok per gudang-satuan
     */
    public function getItemDetail($id)
    {
        $item = Item::with(['kategori'])->find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item tidak ditemukan'
            ], 404);
        }

        // Ambil semua data gudang & satuan untuk item ini
        $gudangItems = ItemGudang::where('item_id', $item->id)
            ->with([
                'gudang:id,nama_gudang',
                'satuan:id,nama_satuan,jumlah,harga_retail,partai_kecil,harga_grosir'
            ])
            ->orderBy('gudang_id')
            ->get()
            ->map(function ($ig) {
                return [
                    'gudang_id'     => $ig->gudang_id,
                    'nama_gudang'   => $ig->gudang->nama_gudang ?? '-',
                    'satuan_id'     => $ig->satuan_id,
                    'nama_satuan'   => $ig->satuan->nama_satuan ?? '-',
                    'konversi'      => (float) ($ig->satuan->jumlah ?? 1),
                    'stok'          => (float) ($ig->stok ?? 0),
                    'total_stok'    => (float) ($ig->total_stok ?? 0),
                    'harga_retail'  => (float) ($ig->satuan->harga_retail ?? 0),
                    'harga_partai_kecil' => (float) ($ig->satuan->partai_kecil ?? 0),
                    'harga_grosir'  => (float) ($ig->satuan->harga_grosir ?? 0),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $item->id,
                'kode_item' => $item->kode_item,
                'nama_item' => $item->nama_item,
                'barcode' => $item->barcode,
                'kategori' => $item->kategori?->nama_kategori ?? '-',
                'deskripsi' => $item->deskripsi,
                'gudang_items' => $gudangItems,
            ]
        ]);
    }

    /**
     * 🔎 API: Get item by barcode (untuk scanner)
     */
    public function getItemByBarcode($barcode)
    {
        $item = Item::with('kategori')
            ->where('barcode', $barcode)
            ->orWhere('kode_item', $barcode)
            ->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item tidak ditemukan'
            ], 404);
        }

        // Redirect ke detail
        return $this->getItemDetail($item->id);
    }
}