<?php

namespace App\Http\Controllers;

use App\Models\Gudang;
use App\Models\Item;
use App\Models\ItemGudang;
use App\Models\ItemPenjualan;
use App\Models\Penjualan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PenjualanCepatController extends Controller
{
    /**
     * ✅ Helper: Update total_stok untuk 1 baris ItemGudang
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

        Log::info("📊 Total stok diupdate (Penjualan Cepat): item_id=$itemId, gudang_id=$gudangId, satuan_id=$satuanId → stok=$stok × konversi=$konversi = total_stok=$totalStokBaru");
    }

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
        // ✅ Tambahkan 'kategori' ke eager loading
        $items = Item::with([
            'gudangItems.gudang',
            'gudangItems.satuan',
            'kategori' // ✅ PENTING
        ])->get();

        $itemsJson = $items->map(function ($i) {
            return [
                'id' => $i->id,
                'kode_item' => $i->kode_item,
                'nama_item' => $i->nama_item,
                'kategori' => $i->kategori?->nama_kategori ?? '', // ✅ PENTING
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
     * ⚠️ TIDAK ADA TAGIHAN - Langsung bayar di kasir
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
            'items.*.keterangan' => 'nullable|string|max:1000', // ✅ Support keterangan
        ]);

        DB::beginTransaction();

        try {
            // 🔹 Tentukan apakah ini draft/pending
            $isDraft = $request->boolean('is_draft', false);
            $statusBayar = $isDraft ? 'unpaid' : 'unpaid'; // unpaid sampai pembayaran dilakukan

            // === 1️⃣ Buat header penjualan ===
            $penjualan = Penjualan::create([
                'no_faktur' => $request->no_faktur,
                'tanggal' => $request->tanggal . ' ' . now()->format('H:i:s'),
                'pelanggan_id' => null, // Penjualan cepat tidak wajib ada pelanggan
                'sub_total' => $request->total,
                'biaya_transport' => 0, // Selalu 0 untuk penjualan cepat (ambil sendiri)
                'total' => $request->total,
                'status_bayar' => $statusBayar,
                'mode' => 'ambil', // ✅ Selalu ambil sendiri untuk penjualan cepat
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
                    'keterangan' => $item['keterangan'] ?? null, // ✅ Simpan keterangan
                    'created_by' => Auth::id(),
                ]);

                // ✅ Kurangi stok (hanya kalau bukan draft)
                if (!$isDraft) {
                    $ig = ItemGudang::where('item_id', $item['item_id'])
                        ->where('gudang_id', $item['gudang_id'])
                        ->where('satuan_id', $item['satuan_id'])
                        ->lockForUpdate()
                        ->first();

                    if ($ig) {
                        $stokLama = $ig->stok ?? 0;
                        $ig->stok = max(0, $stokLama - $item['jumlah']);
                        $ig->save();

                        Log::info("🧾 Stok berkurang (Penjualan Cepat): item_id={$item['item_id']} gudang_id={$item['gudang_id']} dari {$stokLama} ke {$ig->stok}");

                        // ✅ Update total_stok
                        $this->updateTotalStok($item['item_id'], $item['gudang_id'], $item['satuan_id']);
                    }
                }
            }

            // ❌ TIDAK ADA TAGIHAN untuk penjualan cepat

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
            ->with([
                'gudangItems.gudang',
                'gudangItems.satuan',
                'kategori' // ✅ PENTING
            ])
            ->limit(15)
            ->get();

        return response()->json($items->map(function ($i) {
            return [
                'id' => $i->id,
                'kode_item' => $i->kode_item,
                'nama_item' => $i->nama_item,
                'kategori' => $i->kategori?->nama_kategori ?? '', // ✅ PENTING
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
        }));
    }

    /**
     * 🧾 Tampilkan detail penjualan cepat (adjustable mode)
     */
    public function show($id)
    {
        $penjualan = Penjualan::with([
            'items' => function ($query) {
                $query->with([
                    'item' => function ($q) {
                        $q->with([
                            'kategori', // ✅ PENTING
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

        // ✅ Parse keterangan (untuk backward compatibility)
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

        // ✅ Load semua items dengan kategori untuk dropdown
        $items = Item::with([
            'kategori',
            'gudangItems.gudang',
            'gudangItems.satuan'
        ])->get();

        // ✅ Format items JSON (sama seperti di create)
        $itemsJson = $items->map(function ($i) {
            return [
                'id' => $i->id,
                'kode_item' => $i->kode_item,
                'nama_item' => $i->nama_item,
                'kategori' => $i->kategori?->nama_kategori ?? '',
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

        return view('auth.kasir.penjualan-cepat.show', [
            'penjualan' => $penjualan,
            'itemsJson' => $itemsJson,
        ]);
    }

    /**
     * ♻️ Update penjualan cepat dengan stok management
     * ⚠️ TIDAK ADA TAGIHAN
     */
    public function update(Request $request, $id)
    {
        $penjualan = Penjualan::with('items')->findOrFail($id);
        $isDraftRequest = $request->boolean('is_draft', false);

        $rules = [
            'no_faktur' => 'required|string|max:50',
            'tanggal' => 'required|date',
            'total' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.gudang_id' => 'required|exists:gudangs,id',
            'items.*.satuan_id' => 'required|exists:satuans,id',
            'items.*.jumlah' => 'required|numeric|min:0.01',
            'items.*.harga' => 'required|numeric|min:0',
            'items.*.total' => 'required|numeric|min:0',
            'items.*.keterangan' => 'nullable|string|max:1000',
        ];

        $data = $request->validate($rules);

        DB::beginTransaction();
        try {
            $tanggal = isset($data['tanggal']) && $data['tanggal']
                ? \Carbon\Carbon::parse($data['tanggal'] . ' ' . now()->format('H:i:s'))
                : now();

            $statusBayar = $isDraftRequest ? 'unpaid' : $penjualan->status_bayar; // Pertahankan status bayar yang ada

            // 🧾 UPDATE HEADER PENJUALAN
            $penjualan->update([
                'no_faktur' => $data['no_faktur'],
                'tanggal' => $tanggal,
                'sub_total' => $data['total'],
                'total' => $data['total'],
                'status_bayar' => $statusBayar,
                'is_draft' => $isDraftRequest,
                'updated_by' => Auth::id(),
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

                    Log::info("🔙 Stok dikembalikan (Penjualan Cepat): item_id={$oldItem->item_id}, dari {$stokSebelum} ke {$gudangItem->stok}");

                    // ✅ Update total_stok setelah stok dikembalikan
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

                $penjualan->items()->create([
                    'item_id' => $it['item_id'],
                    'gudang_id' => $it['gudang_id'],
                    'satuan_id' => $it['satuan_id'],
                    'jumlah' => $jumlah,
                    'harga' => $harga,
                    'total' => $total,
                    'keterangan' => $it['keterangan'] ?? null,
                    'created_by' => Auth::id(),
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

                        Log::info("📉 Stok dikurangi (Penjualan Cepat): item_id={$it['item_id']}, dari {$stokSebelum} ke {$gudangItem->stok}");
                    }

                    // ✅ Update total_stok setelah stok dikurangi
                    $this->updateTotalStok($it['item_id'], $it['gudang_id'], $it['satuan_id']);
                }
            }

            // ❌ TIDAK ADA TAGIHAN untuk penjualan cepat

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Penjualan cepat berhasil diperbarui.'
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Update PenjualanCepat error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal update penjualan cepat',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 🗑️ Hapus Penjualan Cepat (dengan pengembalian stok)
     * ⚠️ TIDAK ADA TAGIHAN
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
                    'message' => 'Tidak dapat menghapus penjualan yang sudah lunas.'
                ], 400);
            }

            // 🔄 Kembalikan stok semua item & update total_stok
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

                    Log::info("♻️ Stok dikembalikan (hapus Penjualan Cepat): item_id={$item->item_id} dari {$stokLama} ke {$gudangItem->stok}");

                    // ✅ Update total_stok setelah stok dikembalikan
                    $this->updateTotalStok($item->item_id, $item->gudang_id, $item->satuan_id);
                }
            }

            // 📝 Simpan info untuk log
            $noFaktur = $penjualan->no_faktur;

            // 🗑️ Hapus data terkait (TIDAK ADA TAGIHAN)
            $penjualan->items()->delete();
            $penjualan->delete();

            DB::commit();

            Log::info("Penjualan Cepat {$noFaktur} dihapus oleh user " . Auth::id());

            return response()->json([
                'success' => true,
                'message' => "Penjualan cepat {$noFaktur} berhasil dihapus, stok dikembalikan."
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Data penjualan tidak ditemukan.'
            ], 404);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Delete PenjualanCepat error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus penjualan cepat.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ❌ Batalkan/Hapus Draft Penjualan Cepat
     * ⚠️ TIDAK ADA TAGIHAN
     */
    public function cancelDraft($id)
    {
        DB::beginTransaction();
        try {
            $penjualan = Penjualan::with('items')->findOrFail($id);

            // 🛡️ Validasi: Hanya draft yang bisa dibatalkan
            if ($penjualan->is_draft != 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya transaksi draft yang bisa dibatalkan.'
                ], 400);
            }

            // 🔄 Kembalikan stok semua item & update total_stok
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

                    Log::info("♻️ Stok dikembalikan (cancel draft Penjualan Cepat): item_id={$item->item_id} dari {$stokLama} ke {$gudangItem->stok}");

                    // ✅ Update total_stok setelah stok dikembalikan
                    $this->updateTotalStok($item->item_id, $item->gudang_id, $item->satuan_id);
                }
            }

            // 🗑️ Hapus data terkait (TIDAK ADA TAGIHAN)
            $penjualan->items()->delete();
            $penjualan->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi draft berhasil dibatalkan dan stok dikembalikan.'
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Cancel Draft PenjualanCepat error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal membatalkan draft.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
