<?php

namespace App\Http\Controllers;

use App\Models\Gudang;
use App\Models\Item;
use App\Models\ItemGudang;
use App\Models\ItemPenjualan;
use App\Models\LogActivity;
use App\Models\Pelanggan;
use App\Models\Pengiriman;
use App\Models\Penjualan;
use App\Models\Produksi;
use App\Models\Satuan;
use App\Models\TagihanPenjualan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PenjualanController extends Controller
{
    use AuthorizesRequests;

    /**
     * Update total_stok untuk 1 baris ItemGudang
     * total_stok = stok × konversi satuan ke satuan base
     */
    private function updateTotalStok($itemId, $gudangId, $satuanId)
    {
        $ig = ItemGudang::where('item_id', $itemId)
            ->where('gudang_id', $gudangId)
            ->where('satuan_id', $satuanId)
            ->with('satuan:id,jumlah')
            ->first();

        if (!$ig) {
            Log::warning("❌ ItemGudang tidak ditemukan: item_id=$itemId, gudang_id=$gudangId, satuan_id=$satuanId");
            return;
        }

        $stok = (float) ($ig->stok ?? 0);
        $konversi = (float) ($ig->satuan->jumlah ?? 1);
        $totalStokBaru = $stok * $konversi;

        $ig->total_stok = $totalStokBaru;
        $ig->save();

        Log::info("📊 Total stok diupdate: item_id=$itemId, gudang_id=$gudangId, satuan_id=$satuanId → stok=$stok × konversi=$konversi = total_stok=$totalStokBaru");
    }

    /**
     * Cek apakah ada item kategori SPANDEK, jika ada buat data Produksi
     */
    private function createProduksiIfNeeded($penjualan)
    {
        // Cek apakah ada item dengan kategori SPANDEK
        $hasSpandek = false;

        foreach ($penjualan->items as $itemPenjualan) {
            $item = Item::with('kategori')->find($itemPenjualan->item_id);

            if ($item && $item->kategori && strtoupper($item->kategori->nama_kategori) === 'SPANDEK') {
                $hasSpandek = true;
                break; // Cukup ketemu 1 item SPANDEK sudah langsung buat produksi
            }
        }

        // Jika ada SPANDEK, buat data Produksi
        if ($hasSpandek) {
            $noProduksi = $penjualan->no_faktur;

            $produksi = Produksi::create([
                'penjualan_id' => $penjualan->id,
                'no_produksi' => $noProduksi,
                'status' => 'pending',
                'tanggal_mulai' => null,
                'tanggal_selesai' => null,
                'keterangan' => 'Produksi otomatis dari penjualan ' . $penjualan->no_faktur,
                'created_by' => Auth::id(),
            ]);

            LogActivity::create([
                'user_id' => Auth::id(),
                'activity_type' => 'create_produksi',
                'description' => 'Created produksi: ' . $noProduksi . ' from penjualan: ' . $penjualan->no_faktur,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            Log::info("🏭 Produksi dibuat otomatis: no_produksi={$noProduksi}, penjualan_id={$penjualan->id}");

            return $produksi;
        }

        return null;
    }

    public function index()
    {
        // ✅ Check permission view
        $this->authorize('penjualan.view');

        $penjualans = Penjualan::with(['pelanggan', 'items.item', 'pengiriman'])
            ->orderBy('tanggal', 'desc')
            ->get();

        return view('auth.penjualan.index', compact('penjualans'));
    }

    public function create()
    {
        // ✅ Check permission create
        $this->authorize('penjualan.create');

        $pelanggans = Pelanggan::orderBy('nama_pelanggan')->get();
        $gudangs = Gudang::orderBy('nama_gudang')->get();

        $items = Item::with([
            'kategori',
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
        // ✅ Check permission create
        $this->authorize('penjualan.create');

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

                    // ✅ Update total_stok untuk baris ini
                    $this->updateTotalStok($it['item_id'], $it['gudang_id'], $it['satuan_id']);
                }
            }

            // 🚚 3️⃣ Buat Data Pengiriman (jika mode = antar dan bukan draft)
            if ($data['mode'] === 'antar' && !$isDraft) {
                Pengiriman::create([
                    'penjualan_id' => $penjualan->id,
                    'no_pengiriman' => $penjualan->no_faktur,
                    'tanggal_pengiriman' => $penjualan->tanggal,
                    'status_pengiriman' => 'perlu_dikirim',
                    'supir' => null,
                    'created_by' => Auth::id(),
                ]);

                LogActivity::create([
                    'user_id'       => Auth::id(),
                    'activity_type' => 'create_pengiriman',
                    'description'   => 'Created pengiriman for penjualan: ' . $penjualan->no_faktur,
                    'ip_address'    => $request->ip(),
                    'user_agent'    => $request->userAgent(),
                ]);

                Log::info("🚚 Data pengiriman dibuat: no_pengiriman={$penjualan->no_faktur}, penjualan_id={$penjualan->id}");
            }

            // 💰 4️⃣ Buat Tagihan Penjualan (jika unpaid dan bukan draft)
            if ($penjualan->status_bayar === 'unpaid' && !$isDraft) {
                TagihanPenjualan::create([
                    'penjualan_id' => $penjualan->id,
                    'no_tagihan' => $penjualan->no_faktur,
                    'tanggal_tagihan' => now(),
                    'total' => $penjualan->total,
                    'jumlah_bayar' => 0,
                    'sisa' => $penjualan->total,
                    'status_tagihan' => 'belum_lunas',
                    'catatan' => 'Tagihan otomatis dari penjualan ' . $penjualan->no_faktur,
                ]);

                Log::info("💰 Tagihan penjualan dibuat: no_tagihan={$penjualan->no_faktur}, penjualan_id={$penjualan->id}, total={$penjualan->total}");
            }

            DB::commit();



            // 🏭 Buat produksi jika ada item kategori SPANDEK (setelah commit berhasil)
            if (!$isDraft) {
                $penjualan->load('items'); // Load items dulu
                $this->createProduksiIfNeeded($penjualan);
            }




            $message = $isDraft
                ? 'Draft penjualan berhasil disimpan (stok belum dikurangi).'
                : 'Penjualan berhasil disimpan, stok diperbarui.';

            if (!$isDraft) {
                if ($data['mode'] === 'antar') {
                    $message .= ' Data pengiriman telah dibuat.';
                }
                if ($penjualan->status_bayar === 'unpaid') {
                    $message .= ' Tagihan penjualan telah dibuat.';
                }
            }

            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'create_penjualan',
                'description'   => 'Membuat penjualan ' . $penjualan->no_faktur,
                'ip_address'    => $request->ip(),
                'user_agent'    => $request->userAgent(),
            ]);

            return response()->json([
                'message' => $message,
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
                    'kategori' => $item->kategori?->nama_kategori ?? null,
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
            'kategori' => $item->kategori?->nama_kategori ?? '',
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
        $level = $request->get('level', 'retail');
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
        // ✅ Tidak perlu authorize - user dengan permission view sudah bisa akses
        // User tanpa permission update tetap bisa lihat (read-only)

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
            if ($it->keterangan && !$it->catatan_produksi) {
                $parts = explode(' - ', $it->keterangan, 2);
                $it->keterangan = trim($parts[0]);
                $it->catatan_produksi = isset($parts[1]) ? trim($parts[1]) : '';
            } else {
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
        $type = $request->get('type', 'kecil');
        $penjualan = Penjualan::with(['items.item', 'items.satuan', 'createdBy'])->findOrFail($id);

        $generator = new BarcodeGeneratorSVG();
        $barcode = $generator->getBarcode($penjualan->no_faktur, $generator::TYPE_CODE_128);

        if ($type === 'kecil') {
            return view('auth.penjualan.print_kecil', compact('penjualan', 'barcode'));
        } else {
            return view('auth.penjualan.print_besar', compact('penjualan', 'barcode'));
        }
    }

    public function update(Request $request, $id)
    {
        // ✅ Check permission update
        $this->authorize('penjualan.update');

        $penjualan = Penjualan::with('items')->findOrFail($id);
        $isDraftRequest = $request->boolean('is_draft', false);

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

            // 🔁 KEMBALIKAN STOK LAMA & UPDATE TOTAL_STOK
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

                    Log::info("🔙 Stok dikembalikan: item_id={$oldItem->item_id}, dari {$stokSebelum} ke {$gudangItem->stok}");

                    $this->updateTotalStok($oldItem->item_id, $oldItem->gudang_id, $oldItem->satuan_id);
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

                        Log::info("📉 Stok dikurangi: item_id={$it['item_id']}, dari {$stokSebelum} ke {$gudangItem->stok}");
                    }

                    $this->updateTotalStok($it['item_id'], $it['gudang_id'], $it['satuan_id']);
                }
            }

            // 🚚 HANDLE PENGIRIMAN
            $pengiriman = Pengiriman::where('penjualan_id', $penjualan->id)->first();

            if ($data['mode'] === 'antar' && !$isDraftRequest) {
                if ($pengiriman) {
                    $pengiriman->update([
                        'no_pengiriman' => $penjualan->no_faktur,
                        'updated_by' => Auth::id(),
                    ]);
                    Log::info("🚚 Pengiriman diupdate: no_pengiriman={$penjualan->no_faktur}");
                } else {
                    Pengiriman::create([
                        'penjualan_id' => $penjualan->id,
                        'no_pengiriman' => $penjualan->no_faktur,
                        'tanggal_pengiriman' => $penjualan->tanggal,
                        'status_pengiriman' => 'perlu_dikirim',
                        'created_by' => Auth::id(),
                        'supir' => null,
                    ]);
                    Log::info("🚚 Pengiriman dibuat: no_pengiriman={$penjualan->no_faktur}");
                }
            } elseif ($data['mode'] === 'ambil' && $pengiriman) {
                $pengiriman->delete();
                Log::info("🗑️ Pengiriman dihapus karena mode diubah ke 'ambil'");
            }

            // 💰 HANDLE TAGIHAN PENJUALAN
            $tagihan = TagihanPenjualan::where('penjualan_id', $penjualan->id)->first();

            if ($statusBayar === 'unpaid' && !$isDraftRequest) {
                if ($tagihan) {
                    $tagihan->update([
                        'no_tagihan' => $penjualan->no_faktur,
                        'total' => $penjualan->total,
                        'sisa' => max(0, $penjualan->total - ($tagihan->jumlah_bayar ?? 0)),
                        'status_tagihan' => 'belum_lunas',
                        'updated_by' => Auth::id(),
                    ]);
                    Log::info("💰 Tagihan diupdate: no_tagihan={$penjualan->no_faktur}");
                } else {
                    TagihanPenjualan::create([
                        'penjualan_id' => $penjualan->id,
                        'no_tagihan' => $penjualan->no_faktur,
                        'tanggal_tagihan' => now(),
                        'total' => $penjualan->total,
                        'jumlah_bayar' => 0,
                        'sisa' => $penjualan->total,
                        'status_tagihan' => 'belum_lunas',
                        'catatan' => 'Tagihan otomatis dari penjualan ' . $penjualan->no_faktur,
                        'created_by' => Auth::id(),
                    ]);
                    Log::info("💰 Tagihan dibuat: no_tagihan={$penjualan->no_faktur}");
                }
            } elseif ($statusBayar === 'paid' && $tagihan) {
                $tagihan->update([
                    'status_tagihan' => 'lunas',
                    'jumlah_bayar' => $penjualan->total,
                    'sisa' => 0,
                    'updated_by' => Auth::id(),
                ]);
                Log::info("✅ Tagihan diupdate menjadi lunas");
            }


            $penjualan->update([
                'status_bayar' => $statusBayar,
                'updated_by' => Auth::id(),
            ]);
            Log::info("✅ Penjualan diupdate: status_bayar=$statusBayar");


            DB::commit();

            // 🏭 Cek apakah perlu buat produksi
            if (!$isDraftRequest) {
                $penjualan->load('items'); // Load items dulu

                // Cek apakah sudah ada produksi untuk penjualan ini
                $existingProduksi = Produksi::where('penjualan_id', $penjualan->id)->first();

                // Cek apakah masih ada item SPANDEK
                $hasSpandek = false;
                foreach ($penjualan->items as $itemPenjualan) {
                    $item = Item::with('kategori')->find($itemPenjualan->item_id);
                    if ($item && $item->kategori && strtoupper($item->kategori->nama_kategori) === 'SPANDEK') {
                        $hasSpandek = true;
                        break;
                    }
                }

                if ($hasSpandek && !$existingProduksi) {
                    // Buat produksi baru jika belum ada
                    $this->createProduksiIfNeeded($penjualan);
                } elseif (!$hasSpandek && $existingProduksi) {
                    // Hapus produksi jika tidak ada lagi item SPANDEK
                    $existingProduksi->delete();
                    Log::info("🗑️ Produksi dihapus karena tidak ada lagi item SPANDEK");
                }
            }

            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'update_penjualan',
                'description'   => 'Memperbarui penjualan ' . $penjualan->no_faktur,
                'ip_address'    => request()->ip(),
                'user_agent'    => request()->userAgent(),
            ]);

            return response()->json(['message' => 'Penjualan berhasil diperbarui.'], 200);
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
            'status_bayar' => $penjualan->status_bayar,
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
        // ✅ Check permission delete (untuk cancel draft)
        $this->authorize('penjualan.delete');

        DB::beginTransaction();
        try {
            $penjualan = Penjualan::with('items')->findOrFail($id);

            if ($penjualan->is_draft != 1) {
                return response()->json([
                    'message' => 'Hanya transaksi draft yang bisa dibatalkan.'
                ], 400);
            }

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

                    Log::info("♻️ Stok dikembalikan (cancel draft): item_id={$item->item_id} dari {$stokLama} ke {$gudangItem->stok}");

                    $this->updateTotalStok($item->item_id, $item->gudang_id, $item->satuan_id);
                }
            }

            Pengiriman::where('penjualan_id', $penjualan->id)->delete();
            TagihanPenjualan::where('penjualan_id', $penjualan->id)->delete();
            $penjualan->items()->delete();
            $penjualan->delete();

            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'cancel_draft_penjualan',
                'description'   => 'Membatalkan draft penjualan ' . $penjualan->no_faktur,
                'ip_address'    => request()->ip(),
                'user_agent'    => request()->userAgent(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Transaksi draft berhasil dibatalkan dan stok dikembalikan.'
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
        // ✅ Check permission delete
        $this->authorize('penjualan.delete');

        DB::beginTransaction();
        try {
            $penjualan = Penjualan::with('items')->findOrFail($id);

            if ($penjualan->status_bayar === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat menghapus penjualan yang sudah lunas. Silakan gunakan fitur retur.'
                ], 400);
            }

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

                    Log::info("🧩 Stok dikembalikan (hapus): item_id={$item->item_id} dari {$stokLama} ke {$gudangItem->stok}");

                    $this->updateTotalStok($item->item_id, $item->gudang_id, $item->satuan_id);
                }
            }

            $noFaktur = $penjualan->no_faktur;
            $pelanggan = optional($penjualan->pelanggan)->nama_pelanggan ?? 'Customer';

            Pengiriman::where('penjualan_id', $penjualan->id)->delete();
            TagihanPenjualan::where('penjualan_id', $penjualan->id)->delete();
            $penjualan->items()->delete();
            $penjualan->delete();

            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'delete_penjualan',
                'description'   => 'Menghapus penjualan ' . $penjualan->no_faktur,
                'ip_address'    => request()->ip(),
                'user_agent'    => request()->userAgent(),
            ]);


            DB::commit();

            Log::info("Penjualan {$noFaktur} untuk {$pelanggan} dihapus oleh user " . Auth::id());

            return response()->json([
                'success' => true,
                'message' => "Penjualan {$noFaktur} berhasil dihapus, stok dikembalikan."
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
