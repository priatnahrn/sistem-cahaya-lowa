<?php

namespace App\Http\Controllers;

use App\Models\{Pembelian, ItemPembelian, Item, Gudang, ItemGudang, Satuan, Supplier};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Log};
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class PembelianController extends Controller
{
    /**
     * List data pembelian.
     */
    public function index()
    {
        $pembelians = Pembelian::with('supplier')
            ->orderBy('tanggal', 'desc')
            ->paginate(15);

        $pembelians->getCollection()->transform(function ($p) {
            // kalau tanggal kosong atau jamnya default 00:00:00, fallback ke created_at
            if (!$p->tanggal || $p->tanggal->format('H:i:s') === '00:00:00') {
                $p->tanggal = $p->created_at;
            }
            return $p;
        });

        return view('auth.pembelian.index', compact('pembelians'));
    }


    /**
     * Form create pembelian baru.
     */
    public function create()
    {
        $suppliers = Supplier::orderBy('nama_supplier')->get();
        $items     = Item::orderBy('nama_item')->get();
        $gudangs   = Gudang::orderBy('nama_gudang')->get();
        $satuans   = Satuan::orderBy('nama_satuan')->get();

        // harga terakhir (tetap seperti sebelumnya)...
        $hargaTerakhir = ItemPembelian::select('item_id', 'satuan_id', 'harga_beli')
            ->latest()
            ->get()
            ->unique(fn($row) => $row->item_id . '-' . $row->satuan_id)
            ->mapWithKeys(fn($row) => [
                $row->item_id . '-' . $row->satuan_id => $row->harga_beli,
            ]);

        // preview no faktur (format BLddmmyyXXX)
        $todayKey = Carbon::now()->format('dmy'); // mis: 250925
        $row = DB::table('pembelians')
            ->selectRaw("MAX(CAST(SUBSTRING(no_faktur, 9) AS UNSIGNED)) as max_no")
            ->where('no_faktur', 'like', "BL{$todayKey}%")
            ->first();

        $next = ($row->max_no ?? 0) + 1;
        $noFakturPreview = "BL{$todayKey}" . str_pad($next, 3, '0', STR_PAD_LEFT);

        return view('auth.pembelian.create', compact(
            'suppliers',
            'items',
            'gudangs',
            'satuans',
            'hargaTerakhir',
            'noFakturPreview'
        ));
    }

    /**
     * Simpan pembelian baru.
     */
    public function store(Request $request)
    {

        $validated = $request->validate([
            'supplier_id'        => 'nullable|exists:suppliers,id',
            'tanggal'            => 'required|date',
            'status'             => ['required', Rule::in(['paid', 'unpaid', 'return'])],
            'deskripsi'          => 'nullable|string',
            'biaya_transport'    => 'nullable|numeric|min:0',
            'items'              => 'required|array|min:1',
            'items.*.item_id'    => 'required|exists:items,id',
            'items.*.gudang_id'  => 'required|exists:gudangs,id',
            'items.*.satuan_id'  => 'required|exists:satuans,id', // pastikan tidak null
            'items.*.jumlah'     => 'required|numeric|min:1',
            'items.*.harga'      => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // ===== Generate No Faktur BLddmmyyXXX =====
            $today = now()->format('dmy');

            $lastFaktur = DB::table('pembelians')
                ->whereDate('tanggal', $validated['tanggal'])
                ->where('no_faktur', 'like', "BL{$today}%")
                ->orderByDesc('no_faktur')
                ->lockForUpdate()
                ->first();

            $nextNumber = $lastFaktur
                ? (int) substr($lastFaktur->no_faktur, -3) + 1
                : 1;

            $noFaktur = "BL{$today}" . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            // ===== Hitung subtotal dan total =====
            $subTotal       = collect($validated['items'])->sum(fn($i) => $i['jumlah'] * $i['harga']);
            $biayaTransport = $validated['biaya_transport'] ?? 0;

            // ===== Simpan pembelian =====
            $pembelian = Pembelian::create([
                'supplier_id'     => $validated['supplier_id'] ?? null,
                'no_faktur'       => $noFaktur,
                'tanggal'         => $validated['tanggal'] . ' ' . now()->format('H:i:s'),
                'deskripsi'       => $validated['deskripsi'] ?? null,
                'biaya_transport' => $biayaTransport,
                'status'          => $validated['status'],
                'sub_total'       => $subTotal,
                'total'           => $subTotal + $biayaTransport,
                'created_by'      => Auth::id(),
                'updated_by'      => Auth::id(),
            ]);

            // ===== Simpan item + update stok =====
            foreach ($validated['items'] as $it) {
                $last = ItemPembelian::where('item_id', $it['item_id'])
                    ->where('satuan_id', $it['satuan_id'])
                    ->latest()
                    ->first();

                $pembelian->items()->create([
                    'item_id'          => $it['item_id'],
                    'gudang_id'        => $it['gudang_id'],
                    'satuan_id'        => $it['satuan_id'],
                    'jumlah'           => $it['jumlah'],
                    'harga_sebelumnya' => $last?->harga_beli ?? 0,
                    'harga_beli'       => $it['harga'],
                    'total'            => $it['jumlah'] * $it['harga'],
                ]);


                // Cek apakah item sudah ada di gudang
                $itemGudang = ItemGudang::where('item_id', $it['item_id'])
                    ->where('gudang_id', $it['gudang_id'])
                    ->where('satuan_id', $it['satuan_id'])
                    ->first();

                if ($itemGudang) {
                    // Update stok satuan
                    $itemGudang->increment('stok', $it['jumlah']);
                } else {
                    // Buat stok baru
                    $itemGudang = ItemGudang::create([
                        'item_id'   => $it['item_id'],
                        'gudang_id' => $it['gudang_id'],
                        'satuan_id' => $it['satuan_id'],
                        'stok'      => $it['jumlah'],
                        'total_stok' => 0, // nanti dihitung ulang
                    ]);
                }

                // ====== Hitung ulang total stok base unit untuk item + gudang ini ======
                // Ambil semua stok per satuan di gudang ini
                $stokGudang = ItemGudang::where('item_id', $it['item_id'])
                    ->where('gudang_id', $it['gudang_id'])
                    ->with('satuan')
                    ->get();

                // Hitung total dalam base unit
                $totalStok = $stokGudang->sum(fn($row) => $row->stok * $row->satuan->jumlah);

                // Update semua baris item_gudang untuk item+gudang tersebut
                ItemGudang::where('item_id', $it['item_id'])
                    ->where('gudang_id', $it['gudang_id'])
                    ->update(['total_stok' => $totalStok]);
            }

            DB::commit();

            return redirect()->route('pembelian.index')
                ->with('success', "Pembelian berhasil disimpan! No Faktur: $noFaktur");
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => $e->getMessage(),
                'line'  => $e->getLine(),
                'file'  => $e->getFile(),
            ], 500);
        }
    }


    /**
     * Ambil harga terakhir item per satuan.
     */
    public function getLastPrice($itemId, Request $request)
    {
        $last = ItemPembelian::where('item_id', $itemId)
            ->where('satuan_id', $request->query('satuan_id'))
            ->latest()
            ->first();

        return response()->json([
            'harga_sebelumnya' => $last?->harga_beli ?? 0,
        ]);
    }

    /**
     * Detail pembelian.
     */
    public function show($id)
    {
        $pembelian = Pembelian::with(['items.item', 'items.gudang', 'items.satuan', 'supplier'])
            ->findOrFail($id);

        $suppliers = Supplier::all();
        $gudangs   = Gudang::orderBy('nama_gudang')->get();
        $satuans   = Satuan::orderBy('nama_satuan')->get();

        return view('auth.pembelian.show', compact('pembelian', 'suppliers', 'gudangs', 'satuans'));
    }

    /**
     * Update pembelian.
     */
    public function update(Request $request, $id)
    {
        $pembelian = Pembelian::with('items')->findOrFail($id);

        $validated = $request->validate([
            'supplier_id'        => 'nullable|exists:suppliers,id',
            'tanggal'            => 'required|date',
            'status'             => ['required', Rule::in(['paid', 'unpaid', 'return'])],
            'deskripsi'          => 'nullable|string|max:255',
            'biaya_transport'    => 'nullable|numeric|min:0',
            'items'              => 'required|array|min:1',
            'items.*.item_id'    => 'required|exists:items,id',
            'items.*.gudang_id'  => 'required|exists:gudangs,id',
            'items.*.satuan_id'  => 'required|exists:satuans,id',
            'items.*.jumlah'     => 'required|numeric|min:1',
            'items.*.harga'      => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // ===== Hitung ulang subtotal & total =====
            $subTotal       = collect($validated['items'])->sum(fn($i) => $i['jumlah'] * $i['harga']);
            $biayaTransport = $validated['biaya_transport'] ?? 0;

            // ===== Update header pembelian =====
            $pembelian->update([
                'supplier_id'     => $validated['supplier_id'] ?? null,
                'tanggal'         => $validated['tanggal'] . ' ' . now()->format('H:i:s'),
                'deskripsi'       => $validated['deskripsi'] ?? null,
                'biaya_transport' => $biayaTransport,
                'status'          => $validated['status'],
                'sub_total'       => $subTotal,
                'total'           => $subTotal + $biayaTransport,
                'updated_by'      => Auth::id(),
                'updated_at'      => now(),
            ]);

            // ===== Rollback stok lama (hapus item lama) =====
            foreach ($pembelian->items as $old) {
                ItemGudang::where('item_id', $old->item_id)
                    ->where('gudang_id', $old->gudang_id)
                    ->where('satuan_id', $old->satuan_id)
                    ->decrement('stok', $old->jumlah);
            }
            $pembelian->items()->delete();

            // ===== Insert ulang item baru =====
            foreach ($validated['items'] as $it) {
                $last = ItemPembelian::where('item_id', $it['item_id'])
                    ->where('satuan_id', $it['satuan_id'])
                    ->latest()
                    ->first();

                $pembelian->items()->create([
                    'item_id'          => $it['item_id'],
                    'gudang_id'        => $it['gudang_id'],
                    'satuan_id'        => $it['satuan_id'],
                    'jumlah'           => $it['jumlah'],
                    'harga_sebelumnya' => $last?->harga_beli ?? 0,
                    'harga_beli'       => $it['harga'],
                    'total'            => $it['jumlah'] * $it['harga'],
                    'created_by'       => Auth::id(),
                    'updated_by'       => Auth::id(),
                ]);

                // update stok (tambahkan)
                ItemGudang::updateOrCreate(
                    [
                        'item_id'   => $it['item_id'],
                        'gudang_id' => $it['gudang_id'],
                        'satuan_id' => $it['satuan_id'],
                    ],
                    [
                        'stok' => DB::raw('stok + ' . $it['jumlah']),
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'message' => "Pembelian berhasil diperbarui",
                'id'      => $pembelian->id,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal update pembelian', ['error' => $e]);
            return response()->json(['error' => 'Gagal update pembelian: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Hapus pembelian (beserta itemnya) dan kembalikan stok.
     */
    public function destroy($id)
    {
        $pembelian = Pembelian::with('items')->findOrFail($id);

        DB::beginTransaction();
        try {
            // contoh aturan bisnis: blokir hapus kalau sudah lunas (optional)
            if ($pembelian->status === 'paid') {
                if (request()->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'Tidak boleh menghapus pembelian yang sudah lunas.'], 403);
                }
                return back()->withErrors(['error' => 'Tidak boleh menghapus pembelian yang sudah lunas.']);
            }

            foreach ($pembelian->items as $it) {
                $ig = ItemGudang::where('item_id', $it->item_id)
                    ->where('gudang_id', $it->gudang_id)
                    ->where('satuan_id', $it->satuan_id)
                    ->first();

                if ($ig) {
                    $ig->stok = max(0, ($ig->stok ?? 0) - $it->jumlah);
                    $ig->save();
                }
            }

            // simpan no faktur dulu buat respon
            $noFaktur = $pembelian->no_faktur;

            // hapus detail item dan header
            $pembelian->items()->delete();
            $pembelian->delete();

            DB::commit();

            if (request()->wantsJson()) {
                return response()->json(['success' => true, 'message' => "Pembelian $noFaktur berhasil dihapus."]);
            }

            return redirect()->route('pembelian.index')->with('success', "Pembelian (No Faktur: $noFaktur) berhasil dihapus.");
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal menghapus pembelian', ['id' => $id, 'error' => $e->getMessage()]);

            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Gagal menghapus pembelian.'], 500);
            }

            return back()->withErrors(['error' => 'Gagal menghapus pembelian: ' . $e->getMessage()]);
        }
    }
}
