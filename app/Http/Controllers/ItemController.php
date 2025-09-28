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
use Illuminate\Support\Facades\Storage;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Illuminate\Support\Str;


class ItemController extends Controller
{
    /**
     * Tampilkan daftar item untuk index page.
     * Mengembalikan koleksi yang sudah dipetakan sesuai kebutuhan view.
     */
    public function index(Request $request)
    {
        $items = Item::with(['kategori', 'satuans', 'primarySatuan'])
            ->orderBy('id', 'desc')
            ->get();

        return view('auth.items.index', compact('items'));
    }


    /**
     * Form create item.
     */
    public function create()
    {
        $kategori_items = KategoriItem::orderBy('nama_kategori')->get();
        $gudangs = Gudang::orderBy('nama_gudang')->get();

        return view('auth.items.create', compact('kategori_items', 'gudangs'));
    }

    /**
     * Tampilkan detail item.
     */
    public function show($id)
    {
        $item = Item::with(['kategori', 'satuans', 'primarySatuan'])->findOrFail($id);
        $kategori_items = KategoriItem::orderBy('nama_kategori')->get();
        $item_gudangs = ItemGudang::where('item_id', $id)->first();
        $gudangs = Gudang::orderBy('nama_gudang')->get();

        return view('auth.items.show', compact('item', 'kategori_items', 'gudangs', 'item_gudangs'));
    }

    /**
     * Simpan item + satuans + inisialisasi ItemGudang.
     * Menerima form multipart (file foto) dari itemsWizard.
     */

    public function store(Request $request)
    {
        $rules = [
            'kode_item'        => 'nullable|string|max:50|unique:items,kode_item',
            'nama_item'        => 'required|string|max:191',
            'kategori_item_id' => 'required|exists:kategori_items,id',
            'stok_minimal'     => 'nullable|numeric|min:0',
            'foto'             => 'nullable|image|max:5120',
            'satuans'          => 'required|array|min:1',
            'satuans.*.nama_satuan' => 'required|string|max:100',
            'satuans.*.jumlah'      => 'nullable|integer|min:1',
            'satuans.*.is_base'     => 'nullable',
            'satuans.*.harga_retail' => 'nullable|numeric|min:0',
            'satuans.*.partai_kecil' => 'nullable|numeric|min:0',
            'satuans.*.harga_grosir' => 'nullable|numeric|min:0',
            'satuan_primary_index'  => 'nullable|integer|min:0',
        ];

        $validated = $request->validate($rules);

        DB::beginTransaction();
        try {
            // 📌 Ambil kategori
            $kategori = KategoriItem::findOrFail($validated['kategori_item_id']);
            $namaKategori = strtoupper($kategori->nama_kategori);

            // 📌 Tentukan kode item (pakai input user atau auto-generate)
            if (!empty($validated['kode_item'])) {
                $kodeItem = strtoupper($validated['kode_item']);
            } else {
                $words = preg_split('/\s+/', $namaKategori);
                $prefix = '';
                foreach ($words as $w) {
                    $prefix .= Str::substr($w, 0, 1);
                }
                $prefix = Str::substr($prefix, 0, 3);
                $prefix = strtoupper($prefix);

                $length = rand(8, 11);
                $randomNumber = '';
                for ($i = 0; $i < $length; $i++) {
                    $randomNumber .= mt_rand(0, 9);
                }

                $kodeItem = $prefix . '-' . $randomNumber;
            }

            // 📌 Upload foto
            $fotoPath = null;
            if ($request->hasFile('foto')) {
                $ext = $request->file('foto')->getClientOriginalExtension();
                $fileName = $kodeItem . '_' . time() . '.' . $ext;
                $fotoPath = $request->file('foto')->storeAs('items', $fileName, 'public');
            }

            // 📌 Generate barcode (pakai kode item)
            $barcode = $kodeItem;
            $generator = new BarcodeGeneratorSVG();
            $barcodeSVG = $generator->getBarcode($barcode, $generator::TYPE_CODE_128);

            $barcodePath = 'barcodes/' . $barcode . '.svg';
            Storage::disk('public')->put($barcodePath, $barcodeSVG);

            // 📌 Simpan item
            $item = Item::create([
                'kode_item'        => $kodeItem,
                'barcode'          => $barcode,
                'barcode_path'     => $barcodePath,
                'nama_item'        => $validated['nama_item'],
                'kategori_item_id' => $validated['kategori_item_id'],
                'foto_path'        => $fotoPath,
            ]);

            // 📌 Simpan satuans
            $satuanIds = [];
            foreach ($validated['satuans'] as $idx => $s) {
                $isBase = isset($s['is_base']) && in_array($s['is_base'], [true, '1', 1, 'true']);

                $created = Satuan::create([
                    'item_id'      => $item->id,
                    'nama_satuan'  => $s['nama_satuan'],
                    'jumlah'       => $s['jumlah'] ?? 1,
                    'is_base'      => $isBase ? 1 : 0,
                    'harga_retail' => $s['harga_retail'] !== '' ? $s['harga_retail'] : null,
                    'partai_kecil' => $s['partai_kecil'] !== '' ? $s['partai_kecil'] : null,
                    'harga_grosir' => $s['harga_grosir'] !== '' ? $s['harga_grosir'] : null,
                ]);

                $satuanIds[$idx] = $created->id;
            }

            // 📌 Primary satuan
            if ($request->filled('satuan_primary_index')) {
                $primIdx = (int) $request->input('satuan_primary_index');
                if (isset($satuanIds[$primIdx])) {
                    Satuan::where('id', $satuanIds[$primIdx])->update(['is_base' => true]);
                }
            } elseif (!empty($satuanIds)) {
                Satuan::where('id', reset($satuanIds))->update(['is_base' => true]);
            }

            // 📌 Stok awal di semua gudang (tanpa satuan_id)
            $gudangs = Gudang::all();
            $batch = [];
            foreach ($gudangs as $g) {
                $batch[] = [
                    'item_id'     => $item->id,
                    'gudang_id'   => $g->id,
                    'stok_minimal' => $validated['stok_minimal'],
                    'stok'        => 0,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
            }
            if (!empty($batch)) {
                ItemGudang::insert($batch);
            }

            DB::commit();

            return redirect()->route('items.index')
                ->with('success', 'Item berhasil dibuat, foto & barcode otomatis digenerate.');
        } catch (\Throwable $e) {
            DB::rollBack();

            dd('Error Store Item:', $e->getMessage(), $e->getFile(), $e->getLine());
        }
    }





    public function search(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $limit = (int) $request->get('limit', 15);
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $items = Item::with(['satuans'])
            ->where('nama_item', 'like', "%{$q}%")
            ->orWhere('kode_item', 'like', "%{$q}%")
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $results = $items->map(function ($it) {
            $satuans = $it->satuans->map(function ($s) {
                return [
                    'id' => $s->id,
                    'nama_satuan' => $s->nama_satuan,
                    'jumlah' => (int) ($s->jumlah ?? 1),
                    'harga_retail' => $s->harga_retail !== null ? (float) $s->harga_retail : null,
                    'partai_kecil' => $s->partai_kecil !== null ? (float) $s->partai_kecil : null,
                    'harga_grosir' => $s->harga_grosir !== null ? (float) $s->harga_grosir : null,
                ];
            })->values();

            // default satuan (ambil yang is_base jika ada)
            $satuan_default = $it->satuans->firstWhere('is_base', true)?->id
                ?? ($it->satuans->first()?->id ?? null);

            return [
                'id' => $it->id,
                'kode_item' => $it->kode_item,
                'nama_item' => $it->nama_item,
                'satuans' => $satuans,
                'satuan_default' => $satuan_default,
            ];
        })->values();

        return response()->json($results);
    }

    // GET /items/{id}/prices?satuan_id=...&walkin=1&pelanggan_id=...
    public function getPrices($id, Request $request)
    {
        $satuanId = $request->query('satuan_id');
        $walkinFlag = (bool) $request->query('walkin', false); // frontend sends is_walkin/force_walkin as walkin=1
        $pelangganId = $request->query('pelanggan_id');

        $item = Item::with('satuans')->find($id);
        if (!$item) {
            return response()->json(['message' => 'Item tidak ditemukan'], 404);
        }

        // cari satuan target
        $satuan = null;
        if ($satuanId) {
            $satuan = $item->satuans->firstWhere('id', (int)$satuanId);
        }
        if (!$satuan) {
            $satuan = $item->satuans->first();
        }

        $hargaRetail = (float) ($satuan->harga_retail ?? 0);
        $partaiKecil = (float) ($satuan->partai_kecil ?? 0);
        $hargaGrosir = (float) ($satuan->harga_grosir ?? 0);

        // resolve price server-side (prioritas: force walkin -> grosir jika pelanggan terdaftar -> walkin -> retail)
        if ($walkinFlag) {
            $resolved = $partaiKecil ?: $hargaRetail ?: $hargaGrosir;
        } else {
            if ($pelangganId) {
                $resolved = $hargaGrosir ?: $partaiKecil ?: $hargaRetail;
            } else {
                $resolved = $hargaRetail ?: $partaiKecil ?: $hargaGrosir;
            }
        }

        // ambil harga pembelian terakhir (HPP) untuk referensi
        $lastPurchase = ItemPembelian::where('item_id', $id)
            ->when($satuan?->id, fn($q) => $q->where('satuan_id', $satuan->id))
            ->orderByDesc('created_at')
            ->first();

        return response()->json([
            'harga_retail' => $hargaRetail,
            'partai_kecil' => $partaiKecil,
            'harga_grosir' => $hargaGrosir,
            'last_purchase_price' => (float) ($lastPurchase?->harga_beli ?? 0),
            'resolved_price' => (float) $resolved,
            'satuan' => [
                'id' => $satuan->id,
                'nama_satuan' => $satuan->nama_satuan,
                'jumlah' => (int) ($satuan->jumlah ?? 1),
            ],
        ]);
    }

    /**
     * Update simple item (basic fields).
     */
    public function update(Request $request, $id)
    {
        $rules = [
            'kode_item'        => 'required|string|max:50',
            'nama_item'        => 'required|string|max:191',
            'kategori_item_id' => 'required|exists:kategori_items,id',
            'foto'             => 'nullable|image|max:5120',
            'satuans'          => 'required|array|min:1',
            'satuans.*.id'           => 'nullable|exists:satuans,id',
            'satuans.*.nama_satuan'  => 'required|string|max:100',
            'satuans.*.jumlah'       => 'nullable|integer', // ❗ tidak pakai min:1, fallback = 1
            'satuans.*.harga_retail' => 'nullable|numeric|min:0',
            'satuans.*.partai_kecil' => 'nullable|numeric|min:0',
            'satuans.*.harga_grosir' => 'nullable|numeric|min:0',
            'satuan_primary_index'   => 'nullable|integer|min:0',
        ];

        $validated = $request->validate($rules);

        DB::beginTransaction();
        try {
            $item = Item::findOrFail($id);

            // 📌 Update foto kalau ada upload baru
            $fotoPath = $item->foto_path;
            if ($request->hasFile('foto')) {
                if ($fotoPath && Storage::disk('public')->exists($fotoPath)) {
                    Storage::disk('public')->delete($fotoPath);
                }
                $ext = $request->file('foto')->getClientOriginalExtension();
                $fileName = $validated['kode_item'] . '_' . time() . '.' . $ext;
                $fotoPath = $request->file('foto')->storeAs('items', $fileName, 'public');
            }

            // 📌 Update item (kode_item tetap ikut, tapi gak bisa diubah di UI)
            $item->update([
                'kode_item'        => $validated['kode_item'],
                'nama_item'        => $validated['nama_item'],
                'kategori_item_id' => $validated['kategori_item_id'],
                'foto_path'        => $fotoPath,
            ]);

            // 📌 Ambil id satuan lama
            $existingIds = $item->satuans()->pluck('id')->toArray();
            $requestIds  = [];

            foreach ($validated['satuans'] as $s) {
                // kalau ada id, update
                if (!empty($s['id'])) {
                    $sat = $item->satuans()->where('id', $s['id'])->first();
                    if ($sat) {
                        $sat->update([
                            'nama_satuan'  => $s['nama_satuan'],
                            'jumlah'       => $s['jumlah'] ?? 1,
                            'harga_retail' => $s['harga_retail'] !== '' ? $s['harga_retail'] : null,
                            'partai_kecil' => $s['partai_kecil'] !== '' ? $s['partai_kecil'] : null,
                            'harga_grosir' => $s['harga_grosir'] !== '' ? $s['harga_grosir'] : null,
                            'is_base'      => 0,
                        ]);
                        $requestIds[] = $sat->id;
                    }
                } else {
                    // kalau tidak ada id, buat baru
                    $created = $item->satuans()->create([
                        'nama_satuan'  => $s['nama_satuan'],
                        'jumlah'       => $s['jumlah'] ?? 1,
                        'harga_retail' => $s['harga_retail'] !== '' ? $s['harga_retail'] : null,
                        'partai_kecil' => $s['partai_kecil'] !== '' ? $s['partai_kecil'] : null,
                        'harga_grosir' => $s['harga_grosir'] !== '' ? $s['harga_grosir'] : null,
                        'is_base'      => 0,
                    ]);
                    $requestIds[] = $created->id;
                }
            }

            // 📌 Hapus satuan yang tidak ada di request
            $toDelete = array_diff($existingIds, $requestIds);
            if (!empty($toDelete)) {
                $item->satuans()->whereIn('id', $toDelete)->delete();
            }

            // 📌 Set primary satuan
            if ($request->filled('satuan_primary_index')) {
                $primIdx = (int) $request->input('satuan_primary_index');
                if (isset($requestIds[$primIdx])) {
                    $item->satuans()->where('id', $requestIds[$primIdx])->update(['is_base' => true]);
                }
            } elseif (!empty($requestIds)) {
                $item->satuans()->where('id', $requestIds[0])->update(['is_base' => true]);
            }

            DB::commit();

            return redirect()->route('items.index', $item->id)->with('success', 'Item berhasil diperbarui.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error update item: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['error' => 'Terjadi kesalahan saat memperbarui item.'])->withInput();
        }
    }


    /**
     * Hapus item.
     */
    public function destroy($id)
    {
        $item = Item::find($id);
        if (!$item) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Item tidak ditemukan.'], 404);
            }
            return redirect()->route('items.index')->withErrors(['error' => 'Item tidak ditemukan.']);
        }

        try {
            // Hapus relasi
            if ($item->foto_path && Storage::disk('public')->exists($item->foto_path)) {
                Storage::disk('public')->delete($item->foto_path);
            }
            if ($item->barcode_path && Storage::disk('public')->exists($item->barcode_path)) {
                Storage::disk('public')->delete($item->barcode_path);
            }
            $item->satuans()->delete();
            ItemGudang::where('item_id', $item->id)->delete();
            ItemPembelian::where('item_id', $item->id)->delete();

            $item->delete();

            if (request()->expectsJson()) {
                return response()->json(['success' => true]);
            }
            return redirect()->route('items.index')->with('success', 'Item berhasil dihapus.');
        } catch (\Throwable $e) {
            Log::error('Error delete item: ' . $e->getMessage(), ['id' => $id]);

            if (request()->expectsJson()) {
                return response()->json(['error' => 'Terjadi kesalahan saat menghapus data.'], 500);
            }
            return back()->withErrors(['error' => 'Terjadi kesalahan saat menghapus data.'])->withInput();
        }
    }
}
