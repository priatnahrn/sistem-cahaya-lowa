<?php

namespace App\Http\Controllers;

use App\Models\Gudang;
use App\Models\Item;
use App\Models\ItemGudang;
use App\Models\ItemPembelian;
use App\Models\KategoriItem;
use App\Models\Satuan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $items = Item::with(['kategori',  'satuans', 'primarySatuan'])
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($it) {
                return [
                    'id'       => $it->id,
                    'kode'     => $it->kode_item ?? $it->kode,
                    'nama'     => $it->nama_item ?? $it->nama,
                    'kategori' => $it->kategori->nama_kategori ?? null,

                    'stock'    => $it->stok ?? $it->stock,
                    'url'      => route('items.show', $it->id),
                    'deleteUrl' => route('items.destroy', $it->id),
                    'satuans'  => $it->satuans->map(fn($s) => [
                        'nama_satuan' => $s->nama_satuan ?? $s->nama,
                    ]),
                ];
            });

        return view('auth.items.index', ['items' => $items]);
    }


    public function create()
    {
        $kategori_items = KategoriItem::all();

        return view('auth.items.create', compact('kategori_items'));
    }

    public function show($id)
    {
        $item = Item::with(['kategori',  'satuans', 'primarySatuan'])
            ->findOrFail($id);

        // load semua kategori & gudang untuk dropdown
        $kategoris = KategoriItem::all();


        return view('auth.items.show', compact('item', 'kategoris'));
    }


    // public function store(Request $request)
    // {
    //     // VALIDASI: sesuaikan rules dengan field di form
    //     $rules = [
    //         'kode_item' => 'required|string|max:191|unique:items,kode_item',
    //         'nama_item' => 'required|string|max:191',
    //         'kategori_item_id' => 'required|exists:kategori_items,id',
    //         'gudang_id' => 'required|exists:gudangs,id',
    //         'stok_minimal' => 'nullable|integer|min:0',
    //         'foto' => 'nullable|image|max:5120', // 5MB
    //         'satuans' => 'required|array|min:1',
    //         'satuans.*.nama_satuan' => 'required|string|max:100',
    //         'satuans.*.jumlah' => 'nullable|integer|min:1',
    //         'satuans.*.is_base' => 'nullable|boolean',
    //         'satuans.*.harga_retail' => 'nullable|numeric|min:0',
    //         'satuans.*.harga_grosir' => 'nullable|numeric|min:0',
    //         'satuan_primary_index' => 'nullable|integer|min:0',
    //     ];

    //     // Jika kamu masih mengirim hargas[] terpisah, bisa tambahkan validasi untuk itu.
    //     $validated = $request->validate($rules);

    //     DB::beginTransaction();
    //     try {
    //         // handle foto (jika ada)
    //         $fotoPath = null;
    //         if ($request->hasFile('foto')) {
    //             $fotoPath = $request->file('foto')->store('items', 'public');
    //         }

    //         // create item (tanpa primary_satuan_id terlebih dahulu)
    //         $item = Item::create([
    //             'kode_item' => $validated['kode_item'],
    //             'nama_item' => $validated['nama_item'],
    //             'kategori_item_id' => $validated['kategori_item_id'],
    //             'gudang_id' => $validated['gudang_id'],
    //             'stok_minimal' => $validated['stok_minimal'] ?? 0,
    //             'foto_path' => $fotoPath,
    //         ]);

    //         // simpan satuans dan ingat mapping index -> id
    //         $satuanIds = [];
    //         foreach ($request->input('satuans', []) as $idx => $s) {
    //             // Normalisasi nilai is_base (bisa dikirim 'true'/'false' atau 1/0)
    //             $isBase = isset($s['is_base']) && ($s['is_base'] === true || $s['is_base'] === '1' || $s['is_base'] === 'true' || $s['is_base'] == 1);

    //             // Jika ada is_base true, kita akan set false ke satuan lain sekarang (opsional)
    //             if ($isBase) {
    //                 // set semua satuan item lain is_base = false (tidak ada satuan sebelumnya karena baru dibuat)
    //             }

    //             $created = Satuan::create([
    //                 'item_id' => $item->id,
    //                 'nama_satuan' => $s['nama_satuan'],
    //                 'jumlah' => $s['jumlah'] ?? 1,
    //                 'is_base' => $isBase,
    //                 'harga_retail' => isset($s['harga_retail']) && $s['harga_retail'] !== '' ? $s['harga_retail'] : null,
    //                 'harga_grosir' => isset($s['harga_grosir']) && $s['harga_grosir'] !== '' ? $s['harga_grosir'] : null,
    //             ]);

    //             $satuanIds[$idx] = $created->id;
    //         }

    //         // set primary satuan jika index diberikan dan valid
    //         if ($request->filled('satuan_primary_index')) {
    //             $primIdx = (int) $request->input('satuan_primary_index');
    //             if (isset($satuanIds[$primIdx])) {
    //                 $item->primary_satuan_id = $satuanIds[$primIdx];
    //                 $item->save();
    //             }
    //         } else {
    //             // jika tidak ada primary index, set first satuan sebagai primary (opsional)
    //             if (!empty($satuanIds)) {
    //                 $item->primary_satuan_id = reset($satuanIds);
    //                 $item->save();
    //             }
    //         }

    //         DB::commit();

    //         // Jika AJAX (fetch dari Alpine) -> kembalikan JSON
    //         if ($request->wantsJson() || $request->ajax()) {
    //             return response()->json([
    //                 'message' => 'Item berhasil dibuat',
    //                 'redirect' => route('items.index'),
    //             ], 201);
    //         }

    //         return redirect()->route('items.index')->with('success', 'Item berhasil dibuat.');
    //     } catch (\Throwable $e) {
    //         DB::rollBack();
    //         Log::error('Error store item: ' . $e->getMessage());

    //         // jika AJAX, kembalikan JSON error (422/500)
    //         if ($request->wantsJson() || $request->ajax()) {
    //             return response()->json([
    //                 'message' => 'Gagal menyimpan item',
    //                 'error' => $e->getMessage(),
    //             ], 500);
    //         }

    //         return back()->withInput()->withErrors(['error' => 'Gagal menyimpan item: ' . $e->getMessage()]);
    //     }
    // }

    // public function store(Request $request)
    // {
    //     // VALIDASI: sesuaikan rules dengan field di form
    //     $rules = [
    //         'kode_item' => 'required|string|max:191|unique:items,kode_item',
    //         'nama_item' => 'required|string|max:191',
    //         'kategori_item_id' => 'required|exists:kategori_items,id',
    //         'stok_minimal' => 'nullable|integer|min:0',
    //         'foto' => 'nullable|image|max:5120', // 5MB
    //         'satuans' => 'required|array|min:1',
    //         'satuans.*.nama_satuan' => 'required|string|max:100',
    //         'satuans.*.jumlah' => 'nullable|integer|min:1',
    //         'satuans.*.is_base' => 'nullable|boolean',
    //         'satuans.*.harga_retail' => 'nullable|numeric|min:0',
    //         'satuans.*.partai_kecil' => 'nullable|numeric|min:0',
    //         'satuans.*.harga_grosir' => 'nullable|numeric|min:0',
    //         'satuan_primary_index' => 'nullable|integer|min:0',
    //     ];

    //     $validated = $request->validate($rules);

    //     DB::beginTransaction();
    //     try {
    //         // handle foto (jika ada)
    //         $fotoPath = null;
    //         if ($request->hasFile('foto')) {
    //             $fotoPath = $request->file('foto')->store('items', 'public');
    //         }

    //         // create item (tanpa primary_satuan_id terlebih dahulu)
    //         $item = Item::create([
    //             'kode_item' => $validated['kode_item'],
    //             'nama_item' => $validated['nama_item'],
    //             'kategori_item_id' => $validated['kategori_item_id'],

    //             'stok_minimal' => $validated['stok_minimal'] ?? 0,
    //             'foto_path' => $fotoPath,
    //         ]);

    //         // simpan satuans dan mapping index -> id
    //         $satuanIds = [];
    //         foreach ($request->input('satuans', []) as $idx => $s) {
    //             // Normalisasi nilai is_base
    //             $isBase = isset($s['is_base']) && ($s['is_base'] === true || $s['is_base'] === '1' || $s['is_base'] === 'true' || $s['is_base'] == 1);

    //             // Jika is_base true, set is_base false untuk satuan lain (opsional)
    //             if ($isBase) {
    //                 // jika ingin memastikan hanya 1 is_base per item, bisa update existing satuan is_base=false
    //                 // tapi karena item baru, tidak ada satuan sebelumnya
    //             }

    //             $created = Satuan::create([
    //                 'item_id' => $item->id,
    //                 'nama_satuan' => $s['nama_satuan'],
    //                 'jumlah' => $s['jumlah'] ?? 1,
    //                 'is_base' => $isBase,
    //                 'harga_retail' => isset($s['harga_retail']) && $s['harga_retail'] !== '' ? $s['harga_retail'] : null,
    //                 'partai_kecil' => isset($s['partai_kecil']) && $s['partai_kecil'] !== '' ? $s['partai_kecil'] : null,
    //                 'harga_grosir' => isset($s['harga_grosir']) && $s['harga_grosir'] !== '' ? $s['harga_grosir'] : null,
    //             ]);

    //             $satuanIds[$idx] = $created->id;
    //         }

    //         // set primary satuan jika index diberikan dan valid
    //         if ($request->filled('satuan_primary_index')) {
    //             $primIdx = (int) $request->input('satuan_primary_index');
    //             if (isset($satuanIds[$primIdx])) {
    //                 $item->primary_satuan_id = $satuanIds[$primIdx];
    //                 $item->save();
    //             }
    //         } else {
    //             // jika tidak ada primary index, set first satuan sebagai primary (opsional)
    //             if (!empty($satuanIds)) {
    //                 $item->primary_satuan_id = reset($satuanIds);
    //                 $item->save();
    //             }
    //         }

    //         DB::commit();

    //         if ($request->wantsJson() || $request->ajax()) {
    //             return response()->json([
    //                 'message' => 'Item berhasil dibuat',
    //                 'redirect' => route('items.index'),
    //             ], 201);
    //         }

    //         return redirect()->route('items.index')->with('success', 'Item berhasil dibuat.');
    //     } catch (\Throwable $e) {
    //         DB::rollBack();
    //         Log::error('Error store item: ' . $e->getMessage());

    //         if ($request->wantsJson() || $request->ajax()) {
    //             return response()->json([
    //                 'message' => 'Gagal menyimpan item',
    //                 'error' => $e->getMessage(),
    //             ], 500);
    //         }

    //         return back()->withInput()->withErrors(['error' => 'Gagal menyimpan item: ' . $e->getMessage()]);
    //     }
    // }

    public function store(Request $request)
    {
        // VALIDASI
        $rules = [
            'kode_item' => 'required|string|max:191|unique:items,kode_item',
            'nama_item' => 'required|string|max:191',
            'kategori_item_id' => 'required|exists:kategori_items,id',
            'stok_minimal' => 'nullable|integer|min:0',
            'foto' => 'nullable|image|max:5120',
            'satuans' => 'required|array|min:1',
            'satuans.*.nama_satuan' => 'required|string|max:100',
            'satuans.*.jumlah' => 'nullable|integer|min:1',
            'satuans.*.is_base' => 'nullable|boolean',
            'satuans.*.harga_retail' => 'nullable|numeric|min:0',
            'satuans.*.partai_kecil' => 'nullable|numeric|min:0',
            'satuans.*.harga_grosir' => 'nullable|numeric|min:0',
            'satuan_primary_index' => 'nullable|integer|min:0',
        ];

        $validated = $request->validate($rules);

        DB::beginTransaction();
        try {
            // handle foto (jika ada)
            $fotoPath = null;
            if ($request->hasFile('foto')) {
                $fotoPath = $request->file('foto')->store('items', 'public');
            }

            // create item
            $item = Item::create([
                'kode_item' => $validated['kode_item'],
                'nama_item' => $validated['nama_item'],
                'kategori_item_id' => $validated['kategori_item_id'],
                'stok_minimal' => $validated['stok_minimal'] ?? 0,
                'foto_path' => $fotoPath,
            ]);

            // simpan satuans
            $satuanIds = [];
            foreach ($validated['satuans'] as $idx => $s) {
                $isBase = isset($s['is_base']) && ($s['is_base'] === true || $s['is_base'] == 1 || $s['is_base'] === 'true');

                $createdSatuan = Satuan::create([
                    'item_id' => $item->id,
                    'nama_satuan' => $s['nama_satuan'],
                    'jumlah' => $s['jumlah'] ?? 1,
                    'is_base' => $isBase,
                    'harga_retail' => $s['harga_retail'] ?? null,
                    'partai_kecil' => $s['partai_kecil'] ?? null,
                    'harga_grosir' => $s['harga_grosir'] ?? null,
                ]);

                $satuanIds[$idx] = $createdSatuan->id;
            }

            // set primary satuan
            if ($request->filled('satuan_primary_index')) {
                $primIdx = (int) $request->input('satuan_primary_index');
                if (isset($satuanIds[$primIdx])) {
                    $item->primary_satuan_id = $satuanIds[$primIdx];
                    $item->save();
                }
            } else if (!empty($satuanIds)) {
                $item->primary_satuan_id = reset($satuanIds);
                $item->save();
            }

            // Masukkan item ke semua gudang dengan stok awal 0 untuk setiap satuan
            $gudangs = Gudang::all();
            $itemGudangData = [];
            foreach ($gudangs as $g) {
                foreach ($satuanIds as $satuanId) {
                    $itemGudangData[] = [
                        'item_id' => $item->id,
                        'gudang_id' => $g->id,
                        'satuan_id' => $satuanId,
                        'stok' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            ItemGudang::insert($itemGudangData);

            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Item berhasil dibuat dan masuk semua gudang',
                    'redirect' => route('items.index'),
                ], 201);
            }

            return redirect()->route('items.index')->with('success', 'Item berhasil dibuat dan masuk semua gudang.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error store item: ' . $e->getMessage());

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Gagal menyimpan item',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return back()->withInput()->withErrors(['error' => 'Gagal menyimpan item: ' . $e->getMessage()]);
        }
    }



    // public function search(Request $request)
    // {
    //     $q = trim((string) $request->get('q', ''));
    //     $limit = (int) $request->get('limit', 30);
    //     if ($limit <= 0) $limit = 30;

    //     // jangan cari jika terlalu pendek
    //     if (mb_strlen($q) < 2) {
    //         return response()->json([]);
    //     }

    //     $items = Item::with(['satuans', 'primarySatuan'])
    //         ->where(function ($query) use ($q) {
    //             $query->where('nama_item', 'like', "%{$q}%")
    //                 ->orWhere('kode_item', 'like', "%{$q}%");
    //         })
    //         ->orderBy('id', 'desc')
    //         ->limit($limit)
    //         ->get();

    //     $results = $items->map(function ($it) {
    //         // map satuans ke struktur yang frontend harapkan
    //         $satuans = $it->satuans->map(function ($s) {
    //             return [
    //                 'id' => $s->id,
    //                 'nama' => $s->nama_satuan ?? $s->nama ?? null,
    //                 // isi konversi/jumlah/partai_kecil jika ada (dipakai untuk menentukan "unit paling kecil")
    //                 'konversi' => isset($s->jumlah) ? (int) $s->jumlah : (isset($s->konversi) ? (int) $s->konversi : 1),
    //                 'harga_retail' => $s->harga_retail ?? null,
    //                 'harga_grosir' => $s->harga_grosir ?? null,
    //                 'partai_kecil' => $s->partai_kecil ?? null,
    //             ];
    //         })->values();

    //         // tentukan harga default dari primarySatuan jika ada, atau dari satuans pertama
    //         $harga = null;
    //         if ($it->primarySatuan) {
    //             $harga = $it->primarySatuan->harga_retail ?? $it->primarySatuan->harga ?? null;
    //         }
    //         if ($harga === null && $it->satuans->isNotEmpty()) {
    //             $first = $it->satuans->first();
    //             $harga = $first->harga_retail ?? $first->harga ?? null;
    //         }

    //         // tentukan satuan_default: primarySatuan->nama_satuan jika ada,
    //         // kalau tidak, pilih satuan dengan konversi terkecil (nilai numerik paling kecil)
    //         $satuan_default = null;
    //         if ($it->primarySatuan) {
    //             $satuan_default = $it->primarySatuan->nama_satuan ?? ($it->primarySatuan->nama ?? null);
    //         }
    //         if (!$satuan_default && $satuans->isNotEmpty()) {
    //             $sorted = $satuans->sortBy('konversi');
    //             $satuan_default = $sorted->first()['nama'] ?? null;
    //         }

    //         return [
    //             'id' => $it->id,
    //             'kode' => $it->kode_item ?? null,
    //             'nama' => $it->nama_item ?? null,
    //             'harga' => $harga,
    //             'harga_sebelumnya' => $it->harga_terakhir ?? $it->harga ?? null,
    //             'stok' => $it->stok ?? $it->stock ?? null,
    //             'satuans' => $satuans,
    //             'satuan_default' => $satuan_default,
    //             'primary_satuan_id' => $it->primary_satuan_id ?? ($it->primarySatuan->id ?? null),
    //         ];
    //     })->values();

    //     return response()->json($results);
    // }

    // public function search(Request $request)
    // {
    //     $q = trim((string) $request->get('q', ''));
    //     $limit = (int) $request->get('limit', 30);
    //     if ($limit <= 0) $limit = 30;

    //     if (mb_strlen($q) < 2) {
    //         return response()->json([]);
    //     }

    //     // ambil item beserta relasi satuans
    //     $items = Item::with(['satuans'])
    //         ->where(function ($query) use ($q) {
    //             $query->where('nama_item', 'like', "%{$q}%")
    //                 ->orWhere('kode_item', 'like', "%{$q}%");
    //         })
    //         ->orderByDesc('id')
    //         ->limit($limit)
    //         ->get();

    //     $results = $items->map(function ($it) {
    //         // mapping satuan
    //         $satuans = $it->satuans->map(function ($s) {
    //             return [
    //                 'id' => $s->id,
    //                 'nama' => $s->nama_satuan ?? $s->nama ?? null,
    //                 'is_base' => (bool) ($s->is_base ?? false),
    //             ];
    //         })->values();

    //         // ambil satuan default: yang is_base
    //         $satuan_default = $satuans->firstWhere('is_base', true)['nama']
    //             ?? ($satuans->first()['nama'] ?? null);

    //         // ambil harga sebelumnya dari item_pembelians
    //         $lastPurchase = ItemPembelian::where('item_id', $it->id)
    //             ->orderByDesc('created_at')
    //             ->first();

    //         return [
    //             'id' => $it->id,
    //             'kode' => $it->kode_item,
    //             'nama' => $it->nama_item,
    //             // harga baru diinput manual → set 0 dulu
    //             'harga' => 0,
    //             'harga_sebelumnya' => $lastPurchase?->harga_beli ?? 0,
    //             'stok' => $it->stok ?? $it->stock ?? 0,
    //             'satuans' => $satuans,
    //             'satuan_default' => $satuan_default,
    //         ];
    //     })->values();

    //     return response()->json($results);
    // }


  public function search(Request $request)
{
    $q = $request->get('q');

    $items = Item::with('satuans') // kalau ada relasi
        ->where('nama_item', 'like', "%$q%")
        ->orWhere('kode_item', 'like', "%$q%")
        ->limit(10)
        ->get();

    return response()->json($items);
}



    public function update(Request $request, $id)
    {
        // Logika untuk memperbarui item berdasarkan $id
        $validated = $request->validate([
            'nama_item' => 'required|string|max:255',
            'kategori_id' => 'required|exists:kategori_items,id',
            'harga' => 'required|numeric',
            'stok' => 'required|integer',
            'deskripsi' => 'nullable|string',
        ]);

        try {
            $item = \App\Models\Item::find($id);
            if (!$item) {
                return redirect()->route('items.index')->withErrors(['error' => 'Item tidak ditemukan.']);
            }
            $item->update($validated);
            return redirect()->route('items.index')->with('success', 'Item berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Terjadi kesalahan saat memperbarui data.'])->withInput();
        }
    }

    public function destroy($id)
    {
        // Logika untuk menghapus item berdasarkan $id
        try {
            $item = \App\Models\Item::find($id);
            if (!$item) {
                return redirect()->route('items.index')->withErrors(['error' => 'Item tidak ditemukan.']);
            }
            $item->delete();
            return redirect()->route('items.index')->with('success', 'Item berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Terjadi kesalahan saat menghapus data.'])->withInput();
        }
    }
}
