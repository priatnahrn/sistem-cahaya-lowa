<?php

namespace App\Http\Controllers;

use App\Models\Gudang;
use App\Models\Item;
use App\Models\ItemGudang;
use App\Models\ItemPenjualan;
use App\Models\Penjualan;
use App\Models\TagihanPenjualan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PenjualanCepatController extends Controller
{
    /**
     * 🧾 Menampilkan daftar penjualan cepat (no_faktur prefix JC)
     */
    public function index(Request $request)
    {
        $penjualanCepat = Penjualan::query()
            ->with('pelanggan')
            ->where('no_faktur', 'like', 'JC%')
            ->when($request->get('search'), function ($query, $search) {
                $query->where('no_faktur', 'like', "%{$search}%")
                    ->orWhereHas('pelanggan', fn($q) => $q->where('nama_pelanggan', 'like', "%{$search}%"));
            })
            ->orderByDesc('tanggal')
            ->paginate(10);

        // Ambil koleksi murni untuk kebutuhan JSON di Alpine
        $penjualanCepatCollection = $penjualanCepat->getCollection();

        return view('auth.kasir.penjualan-cepat.index', [
            'penjualanCepat' => $penjualanCepatCollection,
        ]);
    }


    /**
     * 🧮 Tampilkan halaman kasir (create)
     */
    public function create()
    {
        // ambil semua item dengan data stok & harga per gudang
        $items = Item::with(['gudangItems.gudang', 'gudangItems.satuan'])->get();

        $itemsJson = $items->map(function ($i) {
            return [
                'id' => $i->id,
                'kode_item' => $i->kode_item,
                'nama_item' => $i->nama_item,
                'gudangs' => $i->gudangItems->map(function ($gi) {
                    $satuan = $gi->satuan;
                    return [
                        'gudang_id' => $gi->gudang_id,
                        'nama_gudang' => $gi->gudang->nama_gudang ?? '-',
                        'satuan_id' => $gi->satuan_id,
                        'nama_satuan' => $satuan->nama_satuan ?? '-',
                        'stok' => (float) ($gi->stok ?? 0),
                        'harga_retail' => (float) ($satuan->harga_retail ?? 0),
                        'harga_partai_kecil' => (float) ($satuan->partai_kecil ?? 0),
                        'harga_grosir' => (float) ($satuan->harga_grosir ?? 0),
                    ];
                })
            ];
        });

        // Generate nomor faktur JC
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
     * 💾 Simpan data penjualan cepat ke database
     */
    public function store(Request $request)
    {
        $request->validate([
            'no_faktur' => 'required|string|max:50',
            'tanggal' => 'required|date',
            'total' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.gudang_id' => 'required|exists:gudangs,id',
            'items.*.satuan_id' => 'required|exists:satuans,id',
            'items.*.jumlah' => 'required|numeric|min:1',
            'items.*.harga' => 'required|numeric|min:0',
            'items.*.total' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            // 🔹 Tentukan apakah ini draft/pending
            $isDraft = $request->boolean('is_draft', false); // true kalau pending
            $statusBayar = $isDraft ? 'unpaid' : 'unpaid'; // tetap unpaid di awal

            // === 1️⃣ Buat header penjualan ===
            $penjualan = Penjualan::create([
                'no_faktur' => $request->no_faktur,
                'tanggal' => $request->tanggal . ' ' . now()->format('H:i:s'),
                'pelanggan_id' => null,
                'sub_total' => $request->total,
                'biaya_transport' => 0,
                'total' => $request->total,
                'status_bayar' => $statusBayar,
                'is_draft' => $isDraft,
                'created_by' => Auth::id(),
            ]);

            // === 2️⃣ Simpan detail item & kurangi stok ===
            foreach ($request->items as $item) {
                ItemPenjualan::create([
                    'penjualan_id' => $penjualan->id,
                    'item_id' => $item['item_id'],
                    'gudang_id' => $item['gudang_id'],
                    'satuan_id' => $item['satuan_id'],
                    'jumlah' => $item['jumlah'],
                    'harga' => $item['harga'],
                    'total' => $item['total'],
                    'created_by' => Auth::id(),
                ]);

                // Kurangi stok di tabel item_gudangs (hanya kalau bukan draft)
                if (! $isDraft) {
                    $ig = ItemGudang::where('item_id', $item['item_id'])
                        ->where('gudang_id', $item['gudang_id'])
                        ->where('satuan_id', $item['satuan_id'])
                        ->lockForUpdate()
                        ->first();

                    if ($ig) {
                        $ig->stok = max(0, ($ig->stok ?? 0) - $item['jumlah']);
                        $ig->save();
                    }
                }
            }

            // === 3️⃣ Buat Tagihan Awal (hanya kalau bukan draft) ===
            if (! $isDraft) {
                TagihanPenjualan::create([
                    'penjualan_id' => $penjualan->id,
                    'no_tagihan' => 'TG' . now()->format('dmy') . str_pad($penjualan->id, 3, '0', STR_PAD_LEFT),
                    'tanggal_tagihan' => now(),
                    'total' => $penjualan->total,
                    'jumlah_bayar' => 0,
                    'sisa' => $penjualan->total,
                    'status_tagihan' => 'belum_lunas',
                    'created_by' => Auth::id(),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $isDraft
                    ? 'Penjualan cepat disimpan sebagai draft (pending)'
                    : 'Penjualan cepat berhasil disimpan',
                'id' => $penjualan->id,
                'no_faktur' => $penjualan->no_faktur,
                'is_draft' => $penjualan->is_draft,
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('PenjualanCepat store error: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan penjualan cepat',
                'error' => $th->getMessage(),
            ], 500);
        }
    }


    /**
     * 🔍 Cari item berdasarkan nama/kode untuk kasir (autocomplete)
     */
    public function searchItems(Request $request)
    {
        $query = trim($request->get('q', ''));

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $items = Item::where('nama_item', 'like', "%{$query}%")
            ->orWhere('kode_item', 'like', "%{$query}%")
            ->with(['gudangItems.gudang', 'gudangItems.satuan'])
            ->limit(15)
            ->get();

        return response()->json($items);
    }


    /**
     * 🧾 Tampilkan detail penjualan cepat berdasarkan ID
     */ public function show($id)
    {
        $penjualan = Penjualan::with([
            'items.item.gudangItems.gudang',
            'items.item.gudangItems.satuan'
        ])->findOrFail($id);

        return view('auth.kasir.penjualan-cepat.show', ['penjualan' => $penjualan]);
    }
}
