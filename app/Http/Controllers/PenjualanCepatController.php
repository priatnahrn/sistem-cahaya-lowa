<?php

namespace App\Http\Controllers;

use App\Models\Gudang;
use App\Models\Item;
use App\Models\ItemGudang;
use App\Models\ItemPenjualan;
use App\Models\Pelanggan;
use App\Models\Penjualan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PenjualanCepatController extends Controller
{

    /**
     * Menampilkan daftar penjualan cepat (no_faktur dimulai JC)
     */
    public function index(Request $request)
    {
        $query = Penjualan::query()
            ->with(['pelanggan', 'pengiriman'])
            ->where('no_faktur', 'like', 'JC%');

        // Optional: cari berdasarkan input pencarian (kasir, pelanggan, faktur)
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('no_faktur', 'like', "%{$search}%")
                    ->orWhereHas('pelanggan', function ($pel) use ($search) {
                        $pel->where('nama_pelanggan', 'like', "%{$search}%");
                    });
            });
        }

        // urutkan berdasarkan tanggal terbaru
        $penjualanCepat = $query->orderByDesc('tanggal')->paginate(10);

        return view('auth.kasir.penjualan-cepat.index', compact('penjualanCepat'));
    }


    /**
     * Tampilkan halaman kasir (create)
     */
    public function create()
    {
        $items = Item::with(['gudangItems.gudang', 'gudangItems.satuan'])->get();

        $itemsJson = $items->map(function ($i) {
            return [
                'id' => $i->id,
                'kode_item' => $i->kode_item,
                'nama_item' => $i->nama_item,
                'gudangs' => $i->gudangItems->map(function ($gi) {
                    $satuan = $gi->satuan; // ambil harga dari relasi Satuan
                    return [
                        'gudang_id' => $gi->gudang_id,
                        'nama_gudang' => $gi->gudang->nama_gudang ?? '-',
                        'satuan_id' => $gi->satuan_id,
                        'nama_satuan' => $satuan->nama_satuan ?? '-',
                        'stok' => $gi->stok,
                        'harga_retail' => $satuan->harga_retail ?? 0,
                        'harga_partai_kecil' => $satuan->partai_kecil ?? 0,
                        'harga_grosir' => $satuan->harga_grosir ?? 0,
                    ];
                })
            ];
        });



        // generate no faktur
        $today = now()->format('dmy');
        $last = Penjualan::whereDate('tanggal', now()->toDateString())
            ->where('no_faktur', 'like', "JC{$today}%")
            ->orderByDesc('no_faktur')
            ->first();

        $next = $last ? ((int) substr($last->no_faktur, strlen("JC{$today}"))) + 1 : 1;
        $noFaktur = "JC{$today}" . str_pad($next, 3, '0', STR_PAD_LEFT);

        return view('auth.kasir.penjualan-cepat.create', compact('itemsJson', 'noFaktur'));
    }



    /**
     * Simpan data penjualan cepat ke database
     */
    public function store(Request $request)
    {
        $request->validate([
            'pelanggan_id' => 'nullable|exists:pelanggans,id',
            'tanggal' => 'required|date',
            'total' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.jumlah' => 'required|numeric|min:1',
            'items.*.harga' => 'required|numeric|min:0',
            'items.*.total' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $penjualan = Penjualan::create([
                'no_faktur' => $request->no_faktur,
                'tanggal' => $request->tanggal,
                'pelanggan_id' => $request->pelanggan_id,
                'total' => $request->total,
                'status_bayar' => 'unpaid',
            ]);

            foreach ($request->items as $item) {
                ItemPenjualan::create([
                    'penjualan_id' => $penjualan->id,
                    'item_id' => $item['item_id'],
                    'jumlah' => $item['jumlah'],
                    'harga' => $item['harga'],
                    'total' => $item['total'],
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'id' => $penjualan->id]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $th->getMessage()], 500);
        }
    }
    /**
     * Cari item untuk kasir (autocomplete)
     */
    public function searchItems(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $items = Item::where('nama_item', 'like', "%{$query}%")
            ->orWhere('kode_item', 'like', "%{$query}%")
            ->with('satuans')
            ->limit(15)
            ->get();

        return response()->json($items);
    }
}
