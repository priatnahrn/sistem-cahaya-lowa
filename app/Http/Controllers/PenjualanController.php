<?php

namespace App\Http\Controllers;

use App\Models\Gudang;
use App\Models\Item;
use App\Models\ItemGudang;
use App\Models\ItemPenjualan;
use App\Models\ItemProduksi;
use App\Models\Pelanggan;
use App\Models\Pengiriman;
use App\Models\Penjualan;
use App\Models\Produksi;
use App\Models\Satuan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Picqer\Barcode\BarcodeGeneratorSVG;

class PenjualanController extends Controller
{

    private function recalculateTotalStokGlobal($itemId)
    {
        $itemGudangs = ItemGudang::where('item_id', $itemId)
            ->with('satuan:id,jumlah')
            ->get();

        if ($itemGudangs->isEmpty()) {
            Log::warning("❌ Tidak ada data ItemGudang untuk item_id=$itemId");
            return;
        }

        // Hitung total stok global (semua gudang)
        $totalStokGlobal = 0;
        foreach ($itemGudangs as $ig) {
            $stok = (float) ($ig->stok ?? 0);
            $konversi = (float) ($ig->satuan->jumlah ?? 1);
            $totalStokGlobal += $stok * $konversi;
        }

        // Update semua baris item ini (di semua gudang)
        foreach ($itemGudangs as $ig) {
            $ig->total_stok = $totalStokGlobal;
            $ig->save();
        }

        Log::info("🌍 Total stok GLOBAL item_id=$itemId diset ke $totalStokGlobal");
    }
    public function index()
    {
        $penjualans = Penjualan::with(['pelanggan', 'items.item'])
            ->orderBy('tanggal', 'desc')
            ->get(); // Gunakan get() karena sudah ada pagination di Alpine.js

        return view('auth.penjualan.index', compact('penjualans'));
    }

    public function create()
    {
        $pelanggans = Pelanggan::orderBy('nama_pelanggan')->get();
        $gudangs = Gudang::orderBy('nama_gudang')->get();

        // ✅ TAMBAHKAN 'kategori' di eager loading
        $items = Item::with([
            'kategori',  // ✅ TAMBAHKAN INI
            'gudangItems.gudang',
            'gudangItems.satuan'
        ])
            ->orderBy('nama_item')
            ->get();

        // preview no faktur
        $today = now()->format('dmy');
        $last = DB::table('penjualans')
            ->whereDate('tanggal', now()->toDateString())
            ->where('no_faktur', 'like', "JL{$today}%")
            ->orderByDesc('no_faktur')
            ->first();

        if ($last) {
            $suffix = substr($last->no_faktur, strlen("JL{$today}"));
            $next = ((int) $suffix) + 1;
        } else {
            $next = 1;
        }
        $noFakturPreview = "JL{$today}" . str_pad($next, 3, '0', STR_PAD_LEFT);

        return view('auth.penjualan.create', compact('pelanggans', 'gudangs', 'items', 'noFakturPreview'));
    }

    public function store(Request $request)
    {
        $isDraft = $request->boolean('is_draft', false);

        $rules = [
            'pelanggan_id'    => 'nullable|exists:pelanggans,id',
            'no_faktur'       => 'required|string|max:191',
            'tanggal'         => 'required|date',
            'deskripsi'       => 'nullable|string',
            'is_walkin'       => 'nullable|boolean',
            'biaya_transport' => 'nullable|numeric|min:0',
            'sub_total'       => 'required|numeric|min:0',
            'total'           => 'required|numeric|min:0',
            'mode'            => 'required|in:ambil,antar',
            'items'           => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.gudang_id' => 'required|exists:gudangs,id',
            'items.*.satuan_id' => 'required|exists:satuans,id',
            'items.*.jumlah'    => 'required|numeric|min:0.01',
            'items.*.harga'     => 'required|numeric|min:0',
            'items.*.total'     => 'required|numeric|min:0',
            'items.*.keterangan' => 'nullable|string|max:1000',
        ];

        $data = $request->validate($rules);

        DB::beginTransaction();
        try {
            // 🧾 1️⃣ Buat Header Penjualan
            $penjualan = Penjualan::create([
                'no_faktur'       => $data['no_faktur'],
                'tanggal'         => $data['tanggal'] . ' ' . now()->format('H:i:s'),
                'pelanggan_id'    => $data['pelanggan_id'] ?? null,
                'deskripsi'       => $data['deskripsi'] ?? null,
                'sub_total'       => $data['sub_total'],
                'biaya_transport' => $data['biaya_transport'] ?? 0,
                'total'           => $data['total'],
                'status_bayar'    => 'unpaid',
                'mode'            => $data['mode'],
                'is_draft'        => $isDraft,
                'created_by'      => Auth::id(),
            ]);

            // 📦 2️⃣ Simpan Detail Item & Kurangi Stok (jika bukan draft)
            foreach ($data['items'] as $it) {
                // Simpan detail item
                ItemPenjualan::create([
                    'penjualan_id' => $penjualan->id,
                    'item_id'      => $it['item_id'],
                    'gudang_id'    => $it['gudang_id'],
                    'satuan_id'    => $it['satuan_id'],
                    'jumlah'       => $it['jumlah'],
                    'harga'        => $it['harga'],
                    'total'        => $it['total'],
                    'keterangan'   => $it['keterangan'] ?? null,
                    'created_by'   => Auth::id(),
                ]);

                if (!$isDraft) {
                    // 🔻 Kurangi stok di satuan yang dijual
                    $ig = ItemGudang::where('item_id', $it['item_id'])
                        ->where('gudang_id', $it['gudang_id'])
                        ->where('satuan_id', $it['satuan_id'])
                        ->lockForUpdate()
                        ->first();

                    if ($ig) {
                        $stokLama = $ig->stok ?? 0;
                        $ig->stok = max(0, $stokLama - $it['jumlah']);
                        $ig->save();

                        Log::info("🧾 Stok berkurang: item_id={$it['item_id']} gudang_id={$it['gudang_id']} satuan_id={$it['satuan_id']} dari {$stokLama} ke {$ig->stok}");
                    }

                    // 🔁 Setelah stok satuan dikurangi, hitung ulang total stok global
                    $this->recalculateTotalStokGlobal($it['item_id']);
                }
            }

            DB::commit();

            return response()->json([
                'message' => $isDraft
                    ? 'Draft penjualan berhasil disimpan (stok belum dikurangi).'
                    : 'Penjualan berhasil disimpan, stok diperbarui dan total stok global disinkronkan.',
                'id' => $penjualan->id,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Penjualan store error: ' . $e->getMessage());

            return response()->json([
                'message' => 'Gagal menyimpan penjualan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }




    /**
     * API: Search items by nama or kode
     */
    public function searchItems(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $items = Item::where(function ($q) use ($query) {
            $q->where('nama_item', 'like', "%{$query}%")
                ->orWhere('kode_item', 'like', "%{$query}%");
        })
            ->with(['kategori', 'gudangItems.gudang', 'gudangItems.satuan'])
            ->limit(20)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nama_item' => $item->nama_item,
                    'kode_item' => $item->kode_item,
                    'kategori' => $item->kategori?->nama_kategori ?? null, // ✅ kirim kategori
                    'gudangs' => $item->gudangItems->map(fn($ig) => [
                        'gudang_id' => $ig->gudang?->id,
                        'nama_gudang' => $ig->gudang?->nama_gudang,
                        'satuan_id' => $ig->satuan?->id,
                        'nama_satuan' => $ig->satuan?->nama_satuan,
                        'stok' => $ig->stok ?? 0,
                        'harga_retail' => $ig->satuan?->harga_retail ?? 0,
                        'harga_partai_kecil' => $ig->satuan?->partai_kecil ?? 0,
                        'harga_grosir' => $ig->satuan?->harga_grosir ?? 0,
                    ])
                ];
            });

        return response()->json($items);
    }



    /**
     * API: Get item by barcode (untuk scanner)
     */
    public function getItemByBarcode($barcode)
    {
        // ✅ TAMBAHKAN eager load 'kategori'
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

        // Ambil semua gudang & stok terkait
        $gudangs = ItemGudang::where('item_id', $item->id)
            ->with(['gudang:id,nama_gudang', 'satuan:id,nama_satuan,harga_retail,partai_kecil,harga_grosir'])
            ->orderBy('gudang_id')
            ->get()
            ->map(function ($ig) {
                return [
                    'gudang_id'     => $ig->gudang_id,
                    'nama_gudang'   => $ig->gudang->nama_gudang ?? '-',
                    'satuan_id'     => $ig->satuan_id,
                    'nama_satuan'   => $ig->satuan->nama_satuan ?? '-',
                    'stok'          => (float) ($ig->stok ?? 0),
                    'harga_retail'  => (float) ($ig->satuan->harga_retail ?? 0),
                    'partai_kecil'  => (float) ($ig->satuan->partai_kecil ?? 0),
                    'harga_grosir'  => (float) ($ig->satuan->harga_grosir ?? 0),
                ];
            });

        return response()->json([
            'id' => $item->id,
            'nama_item' => $item->nama_item,
            'kode_item' => $item->kode_item,
            'barcode' => $item->barcode,
            'kategori' => $item->kategori?->nama_kategori ?? '',  // ✅ TAMBAHKAN INI
            'satuan_default' => $item->satuan_id,
            'gudangs' => $gudangs,
        ]);
    }


    /**
     * API: Get stock for specific item, gudang, satuan
     */
    public function getStock(Request $request)
    {
        $itemId = $request->get('item_id');
        $gudangId = $request->get('gudang_id');
        $satuanId = $request->get('satuan_id');

        if (!$itemId || !$gudangId || !$satuanId) {
            return response()->json([
                'jumlah' => 0,
                'satuan_nama' => ''
            ]);
        }

        $ig = ItemGudang::where('item_id', $itemId)
            ->where('gudang_id', $gudangId)
            ->where('satuan_id', $satuanId)
            ->first();

        $satuan = Satuan::find($satuanId);

        return response()->json([
            'jumlah' => $ig ? ($ig->stok ?? 0) : 0,
            'satuan_nama' => $satuan ? $satuan->nama_satuan : ''
        ]);
    }

    /**
     * API: Get price for item based on satuan and level
     */
    public function getPrice(Request $request)
    {
        $satuanId = $request->get('satuan_id');
        $level = $request->get('level', 'retail'); // retail, partai_kecil, grosir
        $isWalkin = $request->get('is_walkin', false);

        if (!$satuanId) {
            return response()->json(['harga' => 0]);
        }

        $satuan = Satuan::find($satuanId);
        if (!$satuan) {
            return response()->json(['harga' => 0]);
        }

        $hargaRetail = (float) ($satuan->harga_retail ?? 0);
        $partaiKecil = (float) ($satuan->partai_kecil ?? 0);
        $hargaGrosir = (float) ($satuan->harga_grosir ?? 0);

        // Logic sama seperti di store
        if ($isWalkin) {
            $harga = $partaiKecil ?: $hargaRetail ?: $hargaGrosir;
        } else {
            if ($level === 'grosir') {
                $harga = $hargaGrosir ?: $partaiKecil ?: $hargaRetail;
            } elseif ($level === 'partai_kecil') {
                $harga = $partaiKecil ?: $hargaRetail ?: $hargaGrosir;
            } else {
                $harga = $hargaRetail ?: $partaiKecil ?: $hargaGrosir;
            }
        }

        return response()->json([
            'harga' => $harga,
            'harga_retail' => $hargaRetail,
            'partai_kecil' => $partaiKecil,
            'harga_grosir' => $hargaGrosir,
        ]);
    }


    public function show($id)
    {
        $penjualan = Penjualan::with([
            'pelanggan',
            'items' => function ($query) {
                $query->with([
                    'item' => function ($q) {
                        $q->with([
                            'gudangItems' => function ($gq) {
                                $gq->with(['gudang', 'satuan']);
                            }
                        ]);
                    },
                    'gudang',
                    'satuan'
                ]);
            }
        ])->findOrFail($id);

        foreach ($penjualan->items as $it) {
            // Jika database masih model lama (catatan_produksi kosong, tapi keterangan digabung)
            if ($it->keterangan && !$it->catatan_produksi) {
                $parts = explode(' - ', $it->keterangan, 2);
                $it->keterangan = trim($parts[0]);
                $it->catatan_produksi = isset($parts[1]) ? trim($parts[1]) : '';
            } else {
                // Gunakan nilai asli dari database
                $it->keterangan = $it->keterangan ?? '';
                $it->catatan_produksi = $it->catatan_produksi ?? '';
            }
        }


        $gudangs = Gudang::all();
        $items = Item::with(['gudangItems.gudang', 'gudangItems.satuan'])->get();

        return view('auth.penjualan.show', [
            'penjualan' => $penjualan,
            'gudangs' => $gudangs,
            'items' => $items,
        ]);
    }


    public function print(Request $request, $id)
    {
        $type = $request->get('type', 'kecil'); // default kecil
        $penjualan = Penjualan::with(['items.item', 'items.satuan', 'createdBy'])->findOrFail($id);

        // === Generate Barcode dari nomor faktur ===
        $generator = new BarcodeGeneratorSVG();
        $barcode = $generator->getBarcode($penjualan->no_faktur, $generator::TYPE_CODE_128);

        if ($type === 'kecil') {
            // langsung tampilkan halaman HTML thermal
            return view('auth.penjualan.print_kecil', compact('penjualan', 'barcode'));
        } else {
            // untuk nota besar masih bisa pakai PDF kalau mau
            return view('auth.penjualan.print_besar', compact('penjualan', 'barcode'));
        }
    }



    public function update(Request $request, $id)
    {
        $penjualan = Penjualan::with('items')->findOrFail($id);
        $isDraftRequest = $request->boolean('is_draft', false);

        // === VALIDASI DASAR ===
        $rules = [
            'pelanggan_id'      => 'nullable|integer|exists:pelanggans,id',
            'no_faktur'         => 'required|string',
            'tanggal'           => 'nullable|date',
            'deskripsi'         => 'nullable|string',
            'biaya_transport'   => 'nullable|numeric|min:0',
            'mode'              => 'required|in:ambil,antar',
            'status_bayar'      => 'nullable|in:paid,unpaid,return',
            'sub_total'         => 'required|numeric|min:0',
            'total'             => 'required|numeric|min:0',
            'items'             => 'required|array|min:1',
            'items.*.item_id'   => 'required|exists:items,id',
            'items.*.gudang_id' => 'required|exists:gudangs,id',
            'items.*.satuan_id' => 'required|exists:satuans,id',
            'items.*.jumlah'    => 'required|numeric|min:0.01',
            'items.*.harga'     => 'required|numeric|min:0',
            'items.*.keterangan' => 'nullable|string|max:255',
            'items.*.catatan_produksi' => 'nullable|string|max:255',
        ];

        $data = $request->validate($rules);

        DB::beginTransaction();
        try {
            // 🗓️ Tanggal & jam
            $tanggal = isset($data['tanggal']) && $data['tanggal']
                ? \Carbon\Carbon::parse($data['tanggal'] . ' ' . now()->format('H:i:s'))
                : now();

            $statusBayar = $isDraftRequest ? 'unpaid' : ($data['status_bayar'] ?? 'unpaid');

            // 🧾 UPDATE HEADER PENJUALAN
            $penjualan->update([
                'pelanggan_id'    => $data['pelanggan_id'] ?? null,
                'no_faktur'       => $data['no_faktur'],
                'tanggal'         => $tanggal,
                'deskripsi'       => $data['deskripsi'] ?? null,
                'mode'            => $data['mode'],
                'sub_total'       => $data['sub_total'],
                'biaya_transport' => $data['biaya_transport'] ?? 0,
                'total'           => $data['total'],
                'status_bayar'    => $statusBayar,
                'is_draft'        => $isDraftRequest,
                'updated_by'      => Auth::id(),
            ]);

            // 🔁 KEMBALIKAN STOK LAMA
            foreach ($penjualan->items as $oldItem) {
                $gudangItem = ItemGudang::where('item_id', $oldItem->item_id)
                    ->where('gudang_id', $oldItem->gudang_id)
                    ->where('satuan_id', $oldItem->satuan_id)
                    ->lockForUpdate()
                    ->first();

                if ($gudangItem) {
                    $stokSebelum = $gudangItem->stok;
                    $gudangItem->stok += $oldItem->jumlah;
                    $gudangItem->save();

                    Log::info("🔙 Stok dikembalikan: item_id={$oldItem->item_id}, gudang_id={$oldItem->gudang_id}, satuan_id={$oldItem->satuan_id} dari {$stokSebelum} ke {$gudangItem->stok}");
                }
            }

            // 🧹 HAPUS ITEM LAMA
            $penjualan->items()->delete();

            // 📦 TAMBAH ITEM BARU
            foreach ($data['items'] as $it) {
                $jumlah = (float) $it['jumlah'];
                $harga = (float) $it['harga'];
                $total = isset($it['total']) ? (float) $it['total'] : $jumlah * $harga;

                $keteranganGabung = trim(
                    ($it['keterangan'] ?? '') .
                        (isset($it['catatan_produksi']) && $it['catatan_produksi']
                            ? ' - ' . $it['catatan_produksi']
                            : '')
                );

                $penjualan->items()->create([
                    'item_id'   => $it['item_id'],
                    'gudang_id' => $it['gudang_id'],
                    'satuan_id' => $it['satuan_id'],
                    'jumlah'    => $jumlah,
                    'harga'     => $harga,
                    'total'     => $total,
                    'keterangan' => $keteranganGabung ?: null,
                ]);

                if (!$isDraftRequest) {
                    $gudangItem = ItemGudang::where('item_id', $it['item_id'])
                        ->where('gudang_id', $it['gudang_id'])
                        ->where('satuan_id', $it['satuan_id'])
                        ->lockForUpdate()
                        ->first();

                    if ($gudangItem) {
                        $stokSebelum = $gudangItem->stok;
                        $gudangItem->stok = max(0, $stokSebelum - $jumlah);
                        $gudangItem->save();

                        Log::info("📉 Stok dikurangi: item_id={$it['item_id']}, gudang_id={$it['gudang_id']}, satuan_id={$it['satuan_id']} dari {$stokSebelum} ke {$gudangItem->stok}");
                    }

                    // ✅ UPDATE TOTAL STOK GLOBAL
                    $this->recalculateTotalStokGlobal($it['item_id']);
                }
            }

            DB::commit();

            return response()->json(['message' => 'Penjualan berhasil diperbarui dan total stok disinkronkan.'], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Update Penjualan error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Gagal update penjualan',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * API: Cari penjualan berdasarkan kode nota (no_faktur)
     * Digunakan untuk fitur pembayaran (scan barcode / input manual)
     */
    public function searchPenjualan(Request $request)
    {
        $kode = $request->get('kode');

        $penjualan = Penjualan::with([
            'pelanggan:id,nama_pelanggan',
            'items.item:id,nama_item',
            'items.satuan:id,nama_satuan',
            'pembayarans:id,penjualan_id,jumlah_bayar'
        ])
            ->where('no_faktur', $kode)
            ->first();

        if (!$penjualan) {
            return response()->json(['message' => 'Penjualan tidak ditemukan.'], 404);
        }

        $dibayar = $penjualan->pembayarans->sum('jumlah_bayar');

        return response()->json([
            'id' => $penjualan->id,
            'no_faktur' => $penjualan->no_faktur,
            'pelanggan' => $penjualan->pelanggan->nama_pelanggan ?? '-',
            'tanggal' => $penjualan->tanggal->format('Y-m-d H:i:s'),
            'total' => (float) $penjualan->total,
            'dibayar' => (float) $dibayar,
            'sisa' => (float) ($penjualan->sisa ?? max(0, $penjualan->total - $dibayar)),
            'status_bayar' => $penjualan->status_bayar, // ✅ Langsung ambil dari kolom di database
            'items' => $penjualan->items->map(fn($it) => [
                'id' => $it->id,
                'nama_item' => $it->item->nama_item ?? '-',
                'qty' => (float) $it->jumlah,
                'harga' => (float) $it->harga,
                'subtotal' => (float) $it->total,
            ]),
        ]);
    }

    /**
     * ❌ Batalkan/Hapus Draft Penjualan
     */
    public function cancelDraft($id)
    {
        DB::beginTransaction();
        try {
            $penjualan = Penjualan::with('items')->findOrFail($id);

            // 🛡️ Validasi: Hanya draft yang bisa dibatalkan
            if ($penjualan->is_draft != 1) {
                return response()->json([
                    'message' => 'Hanya transaksi draft yang bisa dibatalkan.'
                ], 400);
            }

            // 🔄 Kembalikan stok semua item
            foreach ($penjualan->items as $item) {
                $gudangItem = ItemGudang::where('item_id', $item->item_id)
                    ->where('gudang_id', $item->gudang_id)
                    ->where('satuan_id', $item->satuan_id)
                    ->lockForUpdate()
                    ->first();

                if ($gudangItem) {
                    $stokLama = $gudangItem->stok;
                    $gudangItem->stok += $item->jumlah;
                    $gudangItem->save();

                    Log::info("♻️ Stok dikembalikan (cancel draft): item_id={$item->item_id}, gudang_id={$item->gudang_id}, satuan_id={$item->satuan_id} dari {$stokLama} ke {$gudangItem->stok}");
                }
            }

            // 🗑️ Hapus items dan penjualan
            $penjualan->items()->delete();
            $penjualan->delete();

            // ✅ Recalculate total stok GLOBAL untuk semua item terkait
            $affectedItems = $penjualan->items->pluck('item_id')->unique();
            foreach ($affectedItems as $itemId) {
                $this->recalculateTotalStokGlobal($itemId);
            }

            DB::commit();

            return response()->json([
                'message' => 'Transaksi draft berhasil dibatalkan dan total stok disinkronkan.'
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Cancel Draft error: ' . $e->getMessage());

            return response()->json([
                'message' => 'Gagal membatalkan draft.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * 🗑️ Hapus Penjualan (dengan pengembalian stok)
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $penjualan = Penjualan::with('items')->findOrFail($id);

            // 🛡️ Validasi: Tidak bisa hapus penjualan yang sudah lunas
            if ($penjualan->status_bayar === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat menghapus penjualan yang sudah lunas. Silakan gunakan fitur retur.'
                ], 400);
            }

            // 🔄 Kembalikan stok semua item
            foreach ($penjualan->items as $item) {
                $gudangItem = ItemGudang::where('item_id', $item->item_id)
                    ->where('gudang_id', $item->gudang_id)
                    ->where('satuan_id', $item->satuan_id)
                    ->lockForUpdate()
                    ->first();

                if ($gudangItem) {
                    $stokLama = $gudangItem->stok;
                    $gudangItem->stok += $item->jumlah;
                    $gudangItem->save();

                    Log::info("🧩 Stok dikembalikan (hapus penjualan): item_id={$item->item_id}, gudang_id={$item->gudang_id}, satuan_id={$item->satuan_id} dari {$stokLama} ke {$gudangItem->stok}");
                }
            }

            // 📝 Simpan info untuk log
            $noFaktur = $penjualan->no_faktur;
            $pelanggan = optional($penjualan->pelanggan)->nama_pelanggan ?? 'Customer';

            // 🗑️ Hapus items dan penjualan
            $penjualan->items()->delete();
            $penjualan->delete();

            // ✅ Recalculate total stok GLOBAL untuk semua item terkait
            $affectedItems = $penjualan->items->pluck('item_id')->unique();
            foreach ($affectedItems as $itemId) {
                $this->recalculateTotalStokGlobal($itemId);
            }

            DB::commit();

            Log::info("Penjualan {$noFaktur} untuk {$pelanggan} dihapus oleh user " . Auth::id());

            return response()->json([
                'success' => true,
                'message' => "Penjualan {$noFaktur} berhasil dihapus, stok dikembalikan, dan total stok disinkronkan."
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Data penjualan tidak ditemukan.'
            ], 404);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Delete Penjualan error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus penjualan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
