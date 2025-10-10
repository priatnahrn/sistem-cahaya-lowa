<?php

namespace App\Http\Controllers;

use App\Models\MutasiStok;
use App\Models\MutasiStokItem;
use App\Models\Gudang;
use App\Models\Item;
use App\Models\ItemGudang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MutasiStokController extends Controller
{
    /**
     * Halaman daftar mutasi stok
     */
    public function index()
    {
        // Ambil data mutasi (paginate)
        $mutasis = MutasiStok::with(['gudangAsal', 'gudangTujuan'])
            ->latest()
            ->paginate(10);

        // Ambil semua item yang diperlukan frontend (satuans + itemGudangs untuk stok)
        // NOTE: bila jumlah item sangat besar, pertimbangkan endpoint pencarian daripada load semua
        $items = Item::with(['satuans', 'itemGudangs'])->get();

        // Buat properti ringan untuk frontend (satuan_data & stok_data)
        $items->each(function ($item) {
            $item->stok_data = $item->itemGudangs->map(function ($g) {
                return [
                    'gudang_id' => $g->gudang_id,
                    'satuan_id' => $g->satuan_id,
                    'stok' => $g->stok,
                ];
            })->values();

            $item->satuan_data = $item->satuans->map(function ($s) {
                return [
                    'id' => $s->id,
                    'nama_satuan' => $s->nama_satuan,
                ];
            })->values();
        });

        return view('auth.mutasi-stok.index', compact('mutasis', 'items'));
    }

    /**
     * Halaman Create Mutasi Stok
     */
    public function create()
    {
        // Generate nomor mutasi otomatis (format: MS + dmy + 3-digit increment)
        $dateCode = now()->format('dmy');
        $last = MutasiStok::latest()->first();
        $nextNum = $last ? intval(substr($last->no_mutasi, -3)) + 1 : 1;
        $newCode = 'MS' . $dateCode . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

        // Ambil daftar gudang
        $gudangs = Gudang::select('id', 'nama_gudang')->get();

        // Ambil daftar item beserta satuan & stok di semua gudang
        $items = Item::with(['satuans', 'itemGudangs'])->get();

        // Pasang properti ringan: stok_data & satuan_data
        $items->each(function ($item) {
            $item->stok_data = $item->itemGudangs->map(function ($g) {
                return [
                    'gudang_id' => $g->gudang_id,
                    'satuan_id' => $g->satuan_id,
                    'stok' => $g->stok,
                ];
            })->values();

            $item->satuan_data = $item->satuans->map(function ($s) {
                return [
                    'id' => $s->id,
                    'nama_satuan' => $s->nama_satuan,
                ];
            })->values();
        });

        return view('auth.mutasi-stok.create', compact('newCode', 'gudangs', 'items'));
    }

    /**
     * Simpan Mutasi Stok (create)
     */
    public function store(Request $request)
    {
        $data = $request->json()->all();

        // VALIDASI BASIC
        $validated = validator($data, [
            'no_mutasi' => 'required|unique:mutasi_stoks,no_mutasi',
            'tanggal' => 'required|date',
            'gudang_asal_id' => 'required|exists:gudangs,id',
            'gudang_tujuan_id' => 'required|exists:gudangs,id|different:gudang_asal_id',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.satuan_id' => 'required|exists:satuans,id',
            'items.*.jumlah' => 'required|numeric|min:0.01',
        ])->validate();

        // AGGREGATE permintaan per item+satuan (untuk validasi stok)
        $required = [];
        foreach ($validated['items'] as $row) {
            $key = $row['item_id'] . '|' . $row['satuan_id'];
            $required[$key] = ($required[$key] ?? 0) + floatval($row['jumlah']);
        }

        // Validasi stok tersedia di gudang asal
        $insufficient = [];
        foreach ($required as $key => $qty) {
            [$itemId, $satuanId] = explode('|', $key);
            $stokRecord = ItemGudang::where([
                'item_id' => $itemId,
                'gudang_id' => $validated['gudang_asal_id'],
                'satuan_id' => $satuanId,
            ])->first();

            $available = $stokRecord ? floatval($stokRecord->stok) : 0;
            if ($available < $qty) {
                $item = Item::find($itemId);
                $insufficient[] = "Stok tidak mencukupi untuk '{$item->nama_item}' (dibutuhkan: {$qty}, tersedia: {$available}).";
            }
        }

        if (!empty($insufficient)) {
            return response()->json([
                'success' => false,
                'message' => 'Stok tidak mencukupi.',
                'errors' => $insufficient,
            ], 422);
        }

        // Simpan transaksi
        try {
            DB::beginTransaction();

            $mutasi = MutasiStok::create([
                'no_mutasi' => $validated['no_mutasi'],
                'tanggal_mutasi' => $validated['tanggal'],
                'gudang_asal_id' => $validated['gudang_asal_id'],
                'gudang_tujuan_id' => $validated['gudang_tujuan_id'],
                'created_by' => Auth::id(),
            ]);

            foreach ($validated['items'] as $row) {
                MutasiStokItem::create([
                    'mutasi_stok_id' => $mutasi->id,
                    'item_id' => $row['item_id'],
                    'satuan_id' => $row['satuan_id'],
                    'jumlah' => $row['jumlah'],
                ]);

                // Kurangi stok gudang asal (sudah divalidasi ada)
                $asal = ItemGudang::where([
                    'item_id' => $row['item_id'],
                    'gudang_id' => $validated['gudang_asal_id'],
                    'satuan_id' => $row['satuan_id'],
                ])->first();

                if ($asal) {
                    $asal->decrement('stok', $row['jumlah']);
                }

                // Tambah / create stok gudang tujuan
                $tujuan = ItemGudang::firstOrCreate([
                    'item_id' => $row['item_id'],
                    'gudang_id' => $validated['gudang_tujuan_id'],
                    'satuan_id' => $row['satuan_id'],
                ], ['stok' => 0]);

                $tujuan->increment('stok', $row['jumlah']);
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Mutasi stok berhasil disimpan'], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan mutasi stok.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tampilkan detail mutasi stok (show)
     */
    public function show($id)
    {
        $mutasi = MutasiStok::with(['items.item', 'items.satuan'])->findOrFail($id);
        $gudangs = Gudang::select('id', 'nama_gudang')->get();

        // 🔹 Ambil semua item dengan relasi satuan & stok per gudang
        $items = Item::with(['satuans:id,item_id,nama_satuan', 'itemGudangs:id,item_id,gudang_id,satuan_id,stok'])->get();

        // 🔹 Tambahkan data stok_data & satuan_data agar frontend bisa akses langsung
        $items->each(function ($item) {
            $item->stok_data = $item->itemGudangs->map(function ($g) {
                return [
                    'gudang_id' => $g->gudang_id,
                    'satuan_id' => $g->satuan_id,
                    'stok' => (float) $g->stok,
                ];
            })->values();

            $item->satuan_data = $item->satuans->map(function ($s) {
                return [
                    'id' => $s->id,
                    'nama_satuan' => $s->nama_satuan,
                ];
            })->values();
        });

        // 🔹 Data untuk JS (form)
        $mutasiForJs = [
            'id' => $mutasi->id,
            'no_mutasi' => $mutasi->no_mutasi,
            'tanggal_mutasi' => $mutasi->tanggal_mutasi
                ? date('Y-m-d', strtotime($mutasi->tanggal_mutasi))
                : null,
            'gudang_asal_id' => $mutasi->gudang_asal_id,
            'gudang_tujuan_id' => $mutasi->gudang_tujuan_id,
            'items' => $mutasi->items->map(function ($i) {
                return [
                    'id' => $i->id,
                    'item_id' => $i->item_id,
                    'nama_item' => $i->item?->nama_item ?? '',
                    'jumlah' => (float) $i->jumlah,
                    'satuan_id' => $i->satuan_id,
                    'nama_satuan' => $i->satuan?->nama_satuan ?? '',
                    'query' => $i->item?->nama_item ?? '',
                    'satuans' => $i->item?->satuans?->map(fn($s) => [
                        'id' => $s->id,
                        'nama_satuan' => $s->nama_satuan,
                    ]) ?? collect(),
                ];
            })->values()->toArray(),
        ];

        // 🔹 Data semua item untuk search di frontend
        $itemsArray = $items->map(fn($i) => [
            'id' => $i->id,
            'kode_item' => $i->kode_item,
            'nama_item' => $i->nama_item,
            'satuans' => $i->satuan_data,
            'stok_data' => $i->stok_data,
        ])->toArray();

        // 🔹 Kirim ke view
        return view('auth.mutasi-stok.show', compact('mutasi', 'mutasiForJs', 'gudangs', 'itemsArray'));
    }


    /**
     * Update mutasi stok (edit)
     */
    public function update(Request $request, $id)
    {
        $data = $request->json()->all();

        // VALIDASI dasar
        $validated = validator($data, [
            'tanggal' => 'required|date',
            'gudang_asal_id' => 'required|exists:gudangs,id',
            'gudang_tujuan_id' => 'required|exists:gudangs,id|different:gudang_asal_id',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.satuan_id' => 'required|exists:satuans,id',
            'items.*.jumlah' => 'required|numeric|min:0.01',
        ])->validate();

        try {
            DB::beginTransaction();

            $mutasi = MutasiStok::with('items')->findOrFail($id);

            // Simpan original gudang untuk revert stok
            $origGudangAsal = $mutasi->gudang_asal_id;
            $origGudangTujuan = $mutasi->gudang_tujuan_id;
            $oldItems = $mutasi->items->map(function ($i) {
                return [
                    'item_id' => $i->item_id,
                    'satuan_id' => $i->satuan_id,
                    'jumlah' => $i->jumlah,
                ];
            })->toArray();

            // 1) Revert stok lama (kembalikan ke gudang asal lama, kurangi dari tujuan lama)
            foreach ($oldItems as $old) {
                // tambah kembali ke asal lama
                $asal = ItemGudang::firstOrCreate([
                    'item_id' => $old['item_id'],
                    'gudang_id' => $origGudangAsal,
                    'satuan_id' => $old['satuan_id'],
                ], ['stok' => 0]);
                $asal->increment('stok', $old['jumlah']);

                // kurangi di tujuan lama (jika ada)
                $tujuan = ItemGudang::where([
                    'item_id' => $old['item_id'],
                    'gudang_id' => $origGudangTujuan,
                    'satuan_id' => $old['satuan_id'],
                ])->first();
                if ($tujuan) {
                    $tujuan->decrement('stok', $old['jumlah']);
                }
            }

            // Hapus item lama
            $mutasi->items()->delete();

            // 2) Validasi stok untuk items baru (aggregate per kombinasi item+satuan)
            $required = [];
            foreach ($validated['items'] as $row) {
                $key = $row['item_id'] . '|' . $row['satuan_id'];
                $required[$key] = ($required[$key] ?? 0) + floatval($row['jumlah']);
            }

            $insufficient = [];
            foreach ($required as $key => $qty) {
                [$itemId, $satuanId] = explode('|', $key);

                $stokRecord = ItemGudang::where([
                    'item_id' => $itemId,
                    'gudang_id' => $validated['gudang_asal_id'],
                    'satuan_id' => $satuanId,
                ])->first();

                $available = $stokRecord ? floatval($stokRecord->stok) : 0;
                if ($available < $qty) {
                    $item = Item::find($itemId);
                    $insufficient[] = "Stok tidak mencukupi untuk '{$item->nama_item}' (dibutuhkan: {$qty}, tersedia: {$available}).";
                }
            }

            if (!empty($insufficient)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Stok tidak mencukupi untuk beberapa item.',
                    'errors' => $insufficient,
                ], 422);
            }

            // 3) Update mutasi utama (tanggal, gudang, updated_by)
            $mutasi->update([
                'tanggal_mutasi' => $validated['tanggal'],
                'gudang_asal_id' => $validated['gudang_asal_id'],
                'gudang_tujuan_id' => $validated['gudang_tujuan_id'],
                'updated_by' => Auth::id(),
            ]);

            // 4) Simpan item baru dan update stok
            foreach ($validated['items'] as $row) {
                MutasiStokItem::create([
                    'mutasi_stok_id' => $mutasi->id,
                    'item_id' => $row['item_id'],
                    'satuan_id' => $row['satuan_id'],
                    'jumlah' => $row['jumlah'],
                ]);

                // Kurangi stok dari gudang asal baru
                $asal = ItemGudang::where([
                    'item_id' => $row['item_id'],
                    'gudang_id' => $validated['gudang_asal_id'],
                    'satuan_id' => $row['satuan_id'],
                ])->first();

                if ($asal) {
                    $asal->decrement('stok', $row['jumlah']);
                }

                // Tambah/firstOrCreate untuk tujuan baru
                $tujuan = ItemGudang::firstOrCreate([
                    'item_id' => $row['item_id'],
                    'gudang_id' => $validated['gudang_tujuan_id'],
                    'satuan_id' => $row['satuan_id'],
                ], ['stok' => 0]);

                $tujuan->increment('stok', $row['jumlah']);
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Perubahan mutasi stok berhasil disimpan'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
