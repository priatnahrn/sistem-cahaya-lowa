<?php

namespace App\Http\Controllers;

use App\Models\Gudang;
use App\Models\Item;
use App\Models\ItemGudang;
use App\Models\ItemPembelian;
use App\Models\KategoriItem;
use App\Models\LogActivity;
use App\Models\Satuan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Illuminate\Support\Str;

class ItemController extends Controller
{
    /**
     * Display a listing of items.
     */
    public function index(Request $request)
    {
        $items = Item::with(['kategori', 'satuans', 'primarySatuan', 'gudangItems'])
            ->orderBy('id', 'desc')
            ->get();

        return view('auth.items.index', compact('items'));
    }

    /**
     * Show the form for creating a new item.
     */
    public function create()
    {
        $kategori_items = KategoriItem::orderBy('nama_kategori')->get();
        $gudangs = Gudang::orderBy('nama_gudang')->get();

        return view('auth.items.create', compact('kategori_items', 'gudangs'));
    }

    /**
     * Store a newly created item in storage.
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
            // Generate kode item
            $kategori = KategoriItem::findOrFail($validated['kategori_item_id']);
            $namaKategori = strtoupper($kategori->nama_kategori);

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

            // Upload foto
            $fotoPath = null;
            if ($request->hasFile('foto')) {
                $ext = $request->file('foto')->getClientOriginalExtension();
                $fileName = $kodeItem . '_' . time() . '.' . $ext;
                $fotoPath = $request->file('foto')->storeAs('items', $fileName, 'public');
            }

            // Generate barcode
            $barcode = $kodeItem;
            $generator = new BarcodeGeneratorSVG();
            $barcodeSVG = $generator->getBarcode($barcode, $generator::TYPE_CODE_128);

            $barcodePath = 'barcodes/' . $barcode . '.svg';
            Storage::disk('public')->put($barcodePath, $barcodeSVG);

            // Simpan item
            $item = Item::create([
                'kode_item'        => $kodeItem,
                'barcode'          => $barcode,
                'barcode_path'     => $barcodePath,
                'nama_item'        => $validated['nama_item'],
                'stok_minimal'     => $validated['stok_minimal'],
                'kategori_item_id' => $validated['kategori_item_id'],
                'foto_path'        => $fotoPath,
            ]);

            // Simpan satuans
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

            // Set primary satuan
            if ($request->filled('satuan_primary_index')) {
                $primIdx = (int) $request->input('satuan_primary_index');
                if (isset($satuanIds[$primIdx])) {
                    Satuan::where('id', $satuanIds[$primIdx])->update(['is_base' => true]);
                }
            } elseif (!empty($satuanIds)) {
                Satuan::where('id', reset($satuanIds))->update(['is_base' => true]);
            }

            // Inisialisasi stok di semua gudang
            $gudangs = Gudang::all();
            $batch = [];

            foreach ($gudangs as $g) {
                foreach ($satuanIds as $satuanId) {
                    $batch[] = [
                        'item_id'     => $item->id,
                        'gudang_id'   => $g->id,
                        'satuan_id'   => $satuanId,
                        'stok'        => 0,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ];
                }
            }

            if (!empty($batch)) {
                ItemGudang::insert($batch);
            }

            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'create_item',
                'description'   => 'Created item: ' . $item->nama_item . ' (' . $item->kode_item . ')',
                'ip_address'    => $request->ip(),
                'user_agent'    => $request->userAgent(),
            ]);

            DB::commit();

            return redirect()->route('items.index')
                ->with('success', 'Item berhasil ditambahkan dengan foto & barcode otomatis.');
        } catch (\Throwable $e) {
            DB::rollBack();
            
            Log::error('Error store item: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withErrors(['error' => 'Terjadi kesalahan saat menyimpan item: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified item.
     */
    public function show($id)
    {
        $item = Item::with(['kategori', 'satuans', 'primarySatuan'])->findOrFail($id);
        $kategori_items = KategoriItem::orderBy('nama_kategori')->get();
        $gudangs = Gudang::orderBy('nama_gudang')->get();

        return view('auth.items.show', compact('item', 'kategori_items', 'gudangs'));
    }

    /**
     * Update the specified item in storage.
     */
    public function update(Request $request, $id)
    {
        $rules = [
            'kode_item'        => 'required|string|max:50|unique:items,kode_item,' . $id,
            'nama_item'        => 'required|string|max:191',
            'kategori_item_id' => 'required|exists:kategori_items,id',
            'foto'             => 'nullable|image|max:5120',
            'satuans'          => 'required|array|min:1',
            'satuans.*.id'           => 'nullable|exists:satuans,id',
            'satuans.*.nama_satuan'  => 'required|string|max:100',
            'satuans.*.jumlah'       => 'nullable|integer',
            'satuans.*.harga_retail' => 'nullable|numeric|min:0',
            'satuans.*.partai_kecil' => 'nullable|numeric|min:0',
            'satuans.*.harga_grosir' => 'nullable|numeric|min:0',
            'satuan_primary_index'   => 'nullable|integer|min:0',
        ];

        $validated = $request->validate($rules);

        DB::beginTransaction();
        try {
            $item = Item::findOrFail($id);

            // Update foto jika ada upload baru
            $fotoPath = $item->foto_path;
            if ($request->hasFile('foto')) {
                if ($fotoPath && Storage::disk('public')->exists($fotoPath)) {
                    Storage::disk('public')->delete($fotoPath);
                }
                $ext = $request->file('foto')->getClientOriginalExtension();
                $fileName = $validated['kode_item'] . '_' . time() . '.' . $ext;
                $fotoPath = $request->file('foto')->storeAs('items', $fileName, 'public');
            }

            // Update item
            $item->update([
                'kode_item'        => $validated['kode_item'],
                'nama_item'        => $validated['nama_item'],
                'kategori_item_id' => $validated['kategori_item_id'],
                'foto_path'        => $fotoPath,
            ]);

            // Kelola satuans (update existing, create new, delete removed)
            $existingIds = $item->satuans()->pluck('id')->toArray();
            $requestIds  = [];

            foreach ($validated['satuans'] as $s) {
                if (!empty($s['id'])) {
                    // Update existing satuan
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
                    // Create new satuan
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

            // Hapus satuan yang tidak ada di request
            $toDelete = array_diff($existingIds, $requestIds);
            if (!empty($toDelete)) {
                $item->satuans()->whereIn('id', $toDelete)->delete();
            }

            // Set primary satuan
            if ($request->filled('satuan_primary_index')) {
                $primIdx = (int) $request->input('satuan_primary_index');
                if (isset($requestIds[$primIdx])) {
                    $item->satuans()->where('id', $requestIds[$primIdx])->update(['is_base' => true]);
                }
            } elseif (!empty($requestIds)) {
                $item->satuans()->where('id', $requestIds[0])->update(['is_base' => true]);
            }

            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'update_item',
                'description'   => 'Updated item: ' . $item->nama_item . ' (' . $item->kode_item . ')',
                'ip_address'    => $request->ip(),
                'user_agent'    => $request->userAgent(),
            ]);

            DB::commit();

            return redirect()->route('items.index')
                ->with('success', 'Item berhasil diperbarui.');
        } catch (\Throwable $e) {
            DB::rollBack();
            
            Log::error('Error update item: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withErrors(['error' => 'Terjadi kesalahan saat memperbarui item: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Remove the specified item from storage.
     */
    public function destroy($id)
    {
        try {
            $item = Item::findOrFail($id);

            // Optional: Check jika item masih digunakan di transaksi
            // if ($item->itemPenjualans()->count() > 0) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Item tidak dapat dihapus karena sudah digunakan dalam transaksi.'
            //     ], 422);
            // }

            // Hapus file foto & barcode
            if ($item->foto_path && Storage::disk('public')->exists($item->foto_path)) {
                Storage::disk('public')->delete($item->foto_path);
            }
            if ($item->barcode_path && Storage::disk('public')->exists($item->barcode_path)) {
                Storage::disk('public')->delete($item->barcode_path);
            }

            // Hapus relasi
            $item->satuans()->delete();
            ItemGudang::where('item_id', $item->id)->delete();
            ItemPembelian::where('item_id', $item->id)->delete();

            $item->delete();

            LogActivity::create([
                'user_id'       => Auth::id(),
                'activity_type' => 'delete_item',
                'description'   => 'Deleted item: ' . $item->nama_item . ' (' . $item->kode_item . ')',
                'ip_address'    => request()->ip(),
                'user_agent'    => request()->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Item berhasil dihapus.'
            ]);
        } catch (\Throwable $e) {
            Log::error('Error delete item: ' . $e->getMessage(), ['id' => $id]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search items (for autocomplete/select2).
     */
    public function search(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $limit = (int) $request->get('limit', 15);
        
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $items = Item::with(['satuans', 'kategori'])
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

            $satuan_default = $it->satuans->firstWhere('is_base', true)?->id
                ?? ($it->satuans->first()?->id ?? null);

            return [
                'id' => $it->id,
                'kode_item' => $it->kode_item,
                'nama_item' => $it->nama_item,
                'kategori' => $it->kategori?->nama_kategori ?? '',
                'satuans' => $satuans,
                'satuan_default' => $satuan_default,
            ];
        })->values();

        return response()->json($results);
    }

    /**
     * Get prices for specific item and satuan.
     */
    public function getPrices($id, Request $request)
    {
        $satuanId = $request->query('satuan_id');
        $walkinFlag = (bool) $request->query('walkin', false);
        $pelangganId = $request->query('pelanggan_id');

        $item = Item::with('satuans')->find($id);
        if (!$item) {
            return response()->json(['message' => 'Item tidak ditemukan'], 404);
        }

        // Cari satuan target
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

        // Resolve price logic
        if ($walkinFlag) {
            $resolved = $partaiKecil ?: $hargaRetail ?: $hargaGrosir;
        } else {
            if ($pelangganId) {
                $resolved = $hargaGrosir ?: $partaiKecil ?: $hargaRetail;
            } else {
                $resolved = $hargaRetail ?: $partaiKecil ?: $hargaGrosir;
            }
        }

        // Get last purchase price (HPP)
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
}