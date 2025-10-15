<?php

namespace App\Http\Controllers;

use App\Models\{Pembelian, ItemPembelian, Item, Gudang, ItemGudang, Satuan, Supplier, TagihanPembelian};
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

        // harga terakhir
        $hargaTerakhir = ItemPembelian::select('item_id', 'satuan_id', 'harga_beli')
            ->latest()
            ->get()
            ->unique(fn($row) => $row->item_id . '-' . $row->satuan_id)
            ->mapWithKeys(fn($row) => [
                $row->item_id . '-' . $row->satuan_id => $row->harga_beli,
            ]);

        // preview no faktur (format BLddmmyyXXX)
        $todayKey = Carbon::now()->format('dmy');
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
     * Simpan pembelian baru + Buat Tagihan jika unpaid.
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
            'items.*.satuan_id'  => 'required|exists:satuans,id',
            'items.*.jumlah'     => 'required|numeric|min:1',
            'items.*.harga'      => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // ===== Generate No Faktur =====
            $today = now()->format('dmy');
            $lastFaktur = DB::table('pembelians')
                ->whereDate('tanggal', $validated['tanggal'])
                ->where('no_faktur', 'like', "BL{$today}%")
                ->orderByDesc('no_faktur')
                ->lockForUpdate()
                ->first();

            $nextNumber = $lastFaktur ? (int) substr($lastFaktur->no_faktur, -3) + 1 : 1;
            $noFaktur = "BL{$today}" . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            // ===== Hitung subtotal dan total =====
            $subTotal       = collect($validated['items'])->sum(fn($i) => $i['jumlah'] * $i['harga']);
            $biayaTransport = $validated['biaya_transport'] ?? 0;
            $grandTotal     = $subTotal + $biayaTransport;

            // ===== Simpan pembelian =====
            $pembelian = Pembelian::create([
                'supplier_id'     => $validated['supplier_id'] ?? null,
                'no_faktur'       => $noFaktur,
                'tanggal'         => $validated['tanggal'] . ' ' . now()->format('H:i:s'),
                'deskripsi'       => $validated['deskripsi'] ?? null,
                'biaya_transport' => $biayaTransport,
                'status'          => $validated['status'],
                'sub_total'       => $subTotal,
                'total'           => $grandTotal,
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

                // Update stok per gudang & satuan
                $itemGudang = ItemGudang::updateOrCreate(
                    [
                        'item_id'   => $it['item_id'],
                        'gudang_id' => $it['gudang_id'],
                        'satuan_id' => $it['satuan_id'],
                    ],
                    []
                );

                $itemGudang->stok += $it['jumlah'];
                $itemGudang->total_stok = $itemGudang->stok * ($itemGudang->satuan->jumlah ?? 1);
                $itemGudang->save();
            }

            // ✅ BUAT TAGIHAN OTOMATIS JIKA STATUS = UNPAID
            if ($validated['status'] === 'unpaid') {
                $this->createTagihanPembelian($pembelian);
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
     * ✅ Helper: Buat Tagihan Pembelian Otomatis
     */
    private function createTagihanPembelian(Pembelian $pembelian)
    {
        // Generate No Tagihan (format: TGB-ddmmyy-XXX)
        $todayKey = now()->format('dmy');
        $lastTagihan = TagihanPembelian::where('no_tagihan', 'like', "TBL{$todayKey}%")
            ->orderByDesc('no_tagihan')
            ->lockForUpdate()
            ->first();

        $nextNumber = $lastTagihan ? (int) substr($lastTagihan->no_tagihan, -3) + 1 : 1;
        $noTagihan = "TBL{$todayKey}" . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        // Buat tagihan dengan sisa = total (belum ada pembayaran)
        TagihanPembelian::create([
            'pembelian_id' => $pembelian->id,
            'no_tagihan'   => $noTagihan,
            'tanggal'      => now(),
            'total'        => $pembelian->total,
            'jumlah_bayar' => 0,
            'sisa'         => $pembelian->total,
            'catatan'      => 'Tagihan otomatis dari pembelian ' . $pembelian->no_faktur,
            'created_by'   => Auth::id(),
            'updated_by'   => Auth::id(),
        ]);

        Log::info("✅ Tagihan pembelian berhasil dibuat", [
            'pembelian_id' => $pembelian->id,
            'no_faktur'    => $pembelian->no_faktur,
            'no_tagihan'   => $noTagihan,
            'total'        => $pembelian->total,
        ]);
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
     * Update pembelian + Handle perubahan status.
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
            $statusLama = $pembelian->status;

            // 🧾 Update header pembelian
            $subTotal       = collect($validated['items'])->sum(fn($i) => $i['jumlah'] * $i['harga']);
            $biayaTransport = $validated['biaya_transport'] ?? 0;
            $grandTotal     = $subTotal + $biayaTransport;

            $pembelian->update([
                'supplier_id'     => $validated['supplier_id'] ?? null,
                'tanggal'         => $validated['tanggal'] . ' ' . now()->format('H:i:s'),
                'deskripsi'       => $validated['deskripsi'] ?? null,
                'biaya_transport' => $biayaTransport,
                'status'          => $validated['status'],
                'sub_total'       => $subTotal,
                'total'           => $grandTotal,
                'updated_by'      => Auth::id(),
            ]);

            // 🔍 Siapkan koleksi lama & baru
            $oldItems = $pembelian->items->keyBy(fn($it) => "{$it->item_id}-{$it->gudang_id}-{$it->satuan_id}");
            $newItems = collect($validated['items'])->keyBy(fn($it) => "{$it['item_id']}-{$it['gudang_id']}-{$it['satuan_id']}");

            Log::info('=== [UPDATE PEMBELIAN] Mulai pembaruan stok ===', [
                'pembelian_id' => $pembelian->id,
                'no_faktur' => $pembelian->no_faktur,
            ]);

            // 1️⃣ Item tetap ada → hitung selisih jumlah
            foreach ($newItems as $key => $new) {
                $old = $oldItems->get($key);
                $selisih = $old ? ($new['jumlah'] - $old->jumlah) : $new['jumlah'];

                $itemGudang = ItemGudang::firstOrCreate(
                    [
                        'item_id'   => $new['item_id'],
                        'gudang_id' => $new['gudang_id'],
                        'satuan_id' => $new['satuan_id'],
                    ],
                    ['stok' => 0, 'total_stok' => 0]
                );

                $stokLama = $itemGudang->stok;
                $itemGudang->stok += $selisih;
                if ($itemGudang->stok < 0) $itemGudang->stok = 0;

                $itemGudang->total_stok = $itemGudang->stok * ($itemGudang->satuan->jumlah ?? 1);
                $itemGudang->save();

                Log::info('Perubahan stok item', [
                    'item_id' => $new['item_id'],
                    'gudang_id' => $new['gudang_id'],
                    'satuan_id' => $new['satuan_id'],
                    'jumlah_lama_pembelian' => $old?->jumlah ?? 0,
                    'jumlah_baru_pembelian' => $new['jumlah'],
                    'selisih' => $selisih,
                    'stok_lama' => $stokLama,
                    'stok_baru' => $itemGudang->stok,
                ]);
            }

            // 2️⃣ Item lama yang dihapus dari form → kurangi stoknya
            foreach ($oldItems as $key => $old) {
                if (!$newItems->has($key)) {
                    $itemGudang = ItemGudang::where('item_id', $old->item_id)
                        ->where('gudang_id', $old->gudang_id)
                        ->where('satuan_id', $old->satuan_id)
                        ->first();

                    if ($itemGudang) {
                        $stokLama = $itemGudang->stok;
                        $itemGudang->stok -= $old->jumlah;
                        if ($itemGudang->stok < 0) $itemGudang->stok = 0;
                        $itemGudang->total_stok = $itemGudang->stok * ($itemGudang->satuan->jumlah ?? 1);
                        $itemGudang->save();

                        Log::info('Item dihapus dari pembelian, stok dikurangi', [
                            'item_id' => $old->item_id,
                            'gudang_id' => $old->gudang_id,
                            'satuan_id' => $old->satuan_id,
                            'jumlah_dihapus' => $old->jumlah,
                            'stok_lama' => $stokLama,
                            'stok_baru' => $itemGudang->stok,
                        ]);
                    }
                }
            }

            // 3️⃣ Replace item detail pembelian
            $pembelian->items()->delete();
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
            }

            // ✅ HANDLE PERUBAHAN STATUS
            // Jika berubah dari paid/return → unpaid: Buat tagihan baru
            if ($statusLama !== 'unpaid' && $validated['status'] === 'unpaid') {
                $this->createTagihanPembelian($pembelian);
            }

            // Jika berubah dari unpaid → paid: Hapus tagihan yang belum dibayar
            if ($statusLama === 'unpaid' && $validated['status'] === 'paid') {
                TagihanPembelian::where('pembelian_id', $pembelian->id)
                    ->where('sisa', '>', 0)
                    ->delete();
                    
                Log::info("✅ Tagihan pembelian dihapus karena status berubah ke paid", [
                    'pembelian_id' => $pembelian->id,
                ]);
            }

            Log::info('=== [UPDATE PEMBELIAN] Pembaruan stok selesai ===', [
                'pembelian_id' => $pembelian->id,
                'total_items' => $newItems->count(),
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Pembelian berhasil diperbarui']);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('❌ Gagal update pembelian', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Gagal update pembelian: ' . $e->getMessage()], 500);
        }
    }

    public function getItems($id)
    {
        $pembelian = Pembelian::with(['supplier', 'items.item'])
            ->findOrFail($id);

        return response()->json([
            'supplier' => $pembelian->supplier->nama_supplier,
            'items' => $pembelian->items->map(fn($it) => [
                'id' => $it->id,
                'nama_item' => $it->item->nama_item,
                'jumlah' => $it->jumlah,
                'harga_beli' => $it->harga_beli,
            ])
        ]);
    }

    /**
     * Hapus pembelian (beserta itemnya) dan kembalikan stok.
     */
    public function destroy($id)
    {
        $pembelian = Pembelian::with('items')->findOrFail($id);

        DB::beginTransaction();
        try {
            // Blokir hapus kalau sudah lunas
            if ($pembelian->status === 'paid') {
                if (request()->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'Tidak boleh menghapus pembelian yang sudah lunas.'], 403);
                }
                return back()->withErrors(['error' => 'Tidak boleh menghapus pembelian yang sudah lunas.']);
            }

            // Kembalikan stok
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

            $noFaktur = $pembelian->no_faktur;

            // ✅ Hapus tagihan yang terkait (jika ada)
            TagihanPembelian::where('pembelian_id', $pembelian->id)->delete();

            // Hapus detail item dan header
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