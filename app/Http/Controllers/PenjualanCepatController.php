<?php

namespace App\Http\Controllers;

use App\Models\Gudang;
use App\Models\Item;
use App\Models\ItemGudang;
use App\Models\ItemPenjualan;
use App\Models\LogActivity;
use App\Models\Penjualan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PenjualanCepatController extends Controller
{
    use AuthorizesRequests;

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
        // ✅ Check permission view
        $this->authorize('penjualan_cepat.view');

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
        // ✅ Check permission create
        $this->authorize('penjualan_cepat.create');

        $items = Item::with([
            'gudangItems.gudang',
            'gudangItems.satuan',
            'kategori'
        ])->get();

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
        // ✅ Check permission create
        $this->authorize('penjualan_cepat.create');

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
            'items.*.keterangan' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();

        try {
            $isDraft = $request->boolean('is_draft', false);
            $statusBayar = $isDraft ? 'unpaid' : 'unpaid';

            $penjualan = Penjualan::create([
                'no_faktur' => $request->no_faktur,
                'tanggal' => $request->tanggal . ' ' . now()->format('H:i:s'),
                'pelanggan_id' => null,
                'sub_total' => $request->total,
                'biaya_transport' => 0,
                'total' => $request->total,
                'status_bayar' => $statusBayar,
                'mode' => 'ambil',
                'is_draft' => $isDraft,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            foreach ($request->items as $item) {
                ItemPenjualan::create([
                    'penjualan_id' => $penjualan->id,
                    'item_id' => $item['item_id'],
                    'gudang_id' => $item['gudang_id'],
                    'satuan_id' => $item['satuan_id'],
                    'jumlah' => $item['jumlah'],
                    'harga' => $item['harga'],
                    'total' => $item['total'],
                    'keterangan' => $item['keterangan'] ?? null,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);

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

                        $this->updateTotalStok($item['item_id'], $item['gudang_id'], $item['satuan_id']);
                    }
                }
            }

            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'create_penjualan_cepat',
                'description'   => 'Created penjualan cepat: ' . $penjualan->no_faktur,
                'ip_address'    => $request->ip(),
                'user_agent'    => $request->userAgent(),
            ]);

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
                'kategori'
            ])
            ->limit(15)
            ->get();

        return response()->json($items->map(function ($i) {
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
        }));
    }

    /**
     * 🧾 Tampilkan detail penjualan cepat
     * ✅ Semua user dengan permission view bisa lihat detail (read-only)
     */
    public function show($id)
    {
        // ✅ Tidak perlu authorize - user dengan permission view sudah bisa akses
        // User tanpa permission update tetap bisa lihat (read-only)

        $penjualan = Penjualan::with([
            'items' => function ($query) {
                $query->with([
                    'item' => function ($q) {
                        $q->with([
                            'kategori',
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

        $items = Item::with([
            'kategori',
            'gudangItems.gudang',
            'gudangItems.satuan'
        ])->get();

        $itemsJson = $items->map(function ($i) {
            return [
                'id' => $i->id,
                'kode_item' => $i->kode_item,
                'nama_item' => $i->nama_item,
                'kategori' => $i->kategori?->nama_kategori ?? '',
                'gudangs' => $i->gudangItems->map(function ($ig) {
                    return [
                        'gudang_id' => $ig->gudang_id,
                        'nama_gudang' => $ig->gudang->nama_gudang ?? '-',
                        'satuan_id' => $ig->satuan_id,
                        'nama_satuan' => $ig->satuan->nama_satuan ?? '-',
                        'stok' => (float) ($ig->stok ?? 0),
                        'harga_retail' => (float) ($ig->satuan->harga_retail ?? 0),
                        'harga_partai_kecil' => (float) ($ig->satuan->partai_kecil ?? 0),
                        'harga_grosir' => (float) ($ig->satuan->harga_grosir ?? 0),
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
     */
    public function update(Request $request, $id)
    {
        // ✅ Check permission update
        $this->authorize('penjualan_cepat.update');

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

            $statusBayar = $isDraftRequest ? 'unpaid' : $penjualan->status_bayar;

            $penjualan->update([
                'no_faktur' => $data['no_faktur'],
                'tanggal' => $tanggal,
                'sub_total' => $data['total'],
                'total' => $data['total'],
                'status_bayar' => $statusBayar,
                'is_draft' => $isDraftRequest,
                'updated_by' => Auth::id(),
            ]);

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

                    $this->updateTotalStok($oldItem->item_id, $oldItem->gudang_id, $oldItem->satuan_id);
                }
            }

            $penjualan->items()->delete();

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
                    'updated_by' => Auth::id(),
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

                    $this->updateTotalStok($it['item_id'], $it['gudang_id'], $it['satuan_id']);
                }
            }

            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'update_penjualan_cepat',
                'description'   => 'Updated penjualan cepat: ' . $penjualan->no_faktur,
                'ip_address'    => $request->ip(),
                'user_agent'    => $request->userAgent(),
            ]);

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
     */
    public function destroy($id)
    {
        // ✅ Check permission delete
        $this->authorize('penjualan_cepat.delete');

        DB::beginTransaction();
        try {
            $penjualan = Penjualan::with('items')->findOrFail($id);

            if ($penjualan->status_bayar === 'paid') {
                if (request()->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tidak dapat menghapus penjualan yang sudah lunas.'
                    ], 400);
                }

                return back()->withErrors(['error' => 'Tidak dapat menghapus penjualan yang sudah lunas.']);
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

                    Log::info("♻️ Stok dikembalikan (hapus Penjualan Cepat): item_id={$item->item_id} dari {$stokLama} ke {$gudangItem->stok}");

                    $this->updateTotalStok($item->item_id, $item->gudang_id, $item->satuan_id);
                }
            }

            $noFaktur = $penjualan->no_faktur;

            $penjualan->items()->delete();
            $penjualan->delete();

            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'delete_penjualan_cepat',
                'description'   => 'Deleted penjualan cepat: ' . $noFaktur,
                'ip_address'    => request()->ip(),
                'user_agent'    => request()->userAgent(),
            ]);
            DB::commit();

            Log::info("Penjualan Cepat {$noFaktur} dihapus oleh user " . Auth::id());

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Penjualan cepat {$noFaktur} berhasil dihapus, stok dikembalikan."
                ], 200);
            }

            return redirect()->route('penjualan-cepat.index')->with('success', "Penjualan cepat {$noFaktur} berhasil dihapus.");
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data penjualan tidak ditemukan.'
                ], 404);
            }

            return back()->withErrors(['error' => 'Data penjualan tidak ditemukan.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Delete PenjualanCepat error: ' . $e->getMessage());

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menghapus penjualan cepat.',
                    'error' => $e->getMessage()
                ], 500);
            }

            return back()->withErrors(['error' => 'Gagal menghapus penjualan cepat: ' . $e->getMessage()]);
        }
    }

    /**
     * ❌ Batalkan/Hapus Draft Penjualan Cepat
     */
    public function cancelDraft($id)
    {
        // ✅ Check permission delete (untuk cancel draft)
        $this->authorize('penjualan_cepat.delete');

        DB::beginTransaction();
        try {
            $penjualan = Penjualan::with('items')->findOrFail($id);

            if ($penjualan->is_draft != 1) {
                return response()->json([
                    'success' => false,
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

                    Log::info("♻️ Stok dikembalikan (cancel draft Penjualan Cepat): item_id={$item->item_id} dari {$stokLama} ke {$gudangItem->stok}");

                    $this->updateTotalStok($item->item_id, $item->gudang_id, $item->satuan_id);
                }
            }

            $penjualan->items()->delete();
            $penjualan->delete();

            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'delete_penjualan_cepat',
                'description'   => 'Cancelled draft penjualan cepat: ' . $penjualan->no_faktur,
                'ip_address'    => request()->ip(),
                'user_agent'    => request()->userAgent(),
            ]);

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
