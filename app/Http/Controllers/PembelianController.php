<?php

namespace App\Http\Controllers;

use App\Models\Pembelian;
use App\Models\ItemPembelian;
use App\Models\Item;
use App\Models\Gudang;
use App\Models\ItemGudang;
use App\Models\Satuan;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class PembelianController extends Controller
{
    public function index()
    {
        $pembelians = Pembelian::with('supplier')
            ->orderBy('tanggal', 'desc')
            ->paginate(15);

        return view('auth.pembelian.index', compact('pembelians'));
    }

    /**
     * Show form to create a new pembelian.
     */
    public function create()
    {
        $suppliers = Supplier::orderBy('nama_supplier')->get();
        $items = Item::orderBy('nama_item')->get();
        $gudangs = Gudang::orderBy('nama_gudang')->get();
        $satuans = Satuan::orderBy('nama_satuan')->get();

        // Ambil harga beli terakhir per item + satuan
        $hargaTerakhir = ItemPembelian::select('item_id', 'satuan_id', 'harga_beli')
            ->orderByDesc('created_at')
            ->get()
            ->unique(fn($row) => $row->item_id . '-' . $row->satuan_id)
            ->mapWithKeys(fn($row) => [
                $row->item_id . '-' . $row->satuan_id => $row->harga_beli
            ]);

        // Preview nomor faktur
        $row = DB::table('pembelians')
            ->selectRaw("MAX(CAST(SUBSTRING(no_faktur, 4) AS UNSIGNED)) as max_no")
            ->where('no_faktur', 'like', 'PB-%')
            ->first();

        $next = ($row->max_no ?? 0) + 1;
        $noFakturPreview = 'PB-' . str_pad($next, 6, '0', STR_PAD_LEFT);

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
     * Store new pembelian and its items.
     */
    // public function store(Request $request)
    // {
    //     $validated = $request->validate([
    //         'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
    //         'tanggal' => ['required', 'date'],
    //         'deskripsi' => ['nullable', 'string'],
    //         'biaya_transport' => ['nullable', 'numeric', 'min:0'],
    //         'status' => ['required', Rule::in(['paid','unpaid','return'])],


    //         'items' => ['required', 'array', 'min:1'],
    //         'items.*.item_id' => ['required', 'integer', 'exists:items,id'],
    //         'items.*.gudang_id' => ['required', 'integer', 'exists:gudangs,id'],
    //         'items.*.satuan_id' => ['required', 'integer', 'exists:satuans,id'],
    //         'items.*.jumlah' => ['required', 'numeric', 'min:0'],
    //         'items.*.harga' => ['required', 'numeric', 'min:0'],
    //     ]);

    //     DB::beginTransaction();
    //     try {
    //         // generate nomor faktur otomatis
    //         $row = DB::table('pembelians')
    //             ->selectRaw("MAX(CAST(SUBSTRING(no_faktur, 4) AS UNSIGNED)) as max_no")
    //             ->where('no_faktur', 'like', 'PB-%')
    //             ->lockForUpdate()
    //             ->first();

    //         $next = ($row->max_no ?? 0) + 1;
    //         $noFaktur = 'PB-' . str_pad($next, 6, '0', STR_PAD_LEFT);

    //         // buat pembelian
    //         $pembelian = new Pembelian();
    //         $pembelian->supplier_id = $validated['supplier_id'] ?? null;
    //         $pembelian->no_faktur = $noFaktur;
    //         $pembelian->tanggal = $validated['tanggal'];
    //         $pembelian->deskripsi = $validated['deskripsi'] ?? null;
    //         $pembelian->status = $validated['status'] ?? 'unpaid';

    //         $subTotal = collect($validated['items'])->sum(fn($it) => $it['jumlah'] * $it['harga']);
    //         $biayaTransport = $validated['biaya_transport'] ?? 0;
    //         $pembelian->sub_total = $subTotal;
    //         $pembelian->biaya_transport = $biayaTransport;
    //         $pembelian->total = $subTotal + $biayaTransport;

    //         if (Auth::check()) {
    //             $pembelian->created_by = Auth::id();
    //             $pembelian->updated_by = Auth::id();
    //         }

    //         $pembelian->save();

    //         foreach ($validated['items'] as $it) {
    //             // cari harga beli terakhir untuk kombinasi item_id + satuan_id
    //             $lastPembelian = ItemPembelian::where('item_id', $it['item_id'])
    //                 ->where('satuan_id', $it['satuan_id'])
    //                 ->orderByDesc('created_at')
    //                 ->first();

    //             $hargaSebelumnya = $lastPembelian ? $lastPembelian->harga_beli : null;

    //             // simpan detail pembelian
    //             $pembelian->items()->create([
    //                 'item_id' => $it['item_id'],
    //                 'gudang_id' => $it['gudang_id'],
    //                 'satuan_id' => $it['satuan_id'],
    //                 'jumlah' => $it['jumlah'],
    //                 'harga_sebelumnya' => $hargaSebelumnya,
    //                 'harga_beli' => $it['harga'],
    //                 'total' => $it['jumlah'] * $it['harga'],
    //             ]);

    //             // ✅ update stok di item_gudang (per item + gudang + satuan)
    //             ItemGudang::updateOrCreate(
    //                 [
    //                     'item_id'   => $it['item_id'],
    //                     'gudang_id' => $it['gudang_id'],
    //                     'satuan_id' => $it['satuan_id'],
    //                 ],
    //                 [
    //                     'stok' => DB::raw('stok + ' . $it['jumlah'])
    //                 ]
    //             );
    //         }

    //         DB::commit();

    //         return redirect()->route('pembelian.index')
    //             ->with('success', 'Pembelian berhasil disimpan. No Faktur: ' . $noFaktur);
    //     } catch (\Throwable $e) {
    //         DB::rollBack();
    //         Log::error('Gagal menyimpan pembelian', ['error' => $e]);
    //         return back()->withInput()->withErrors(['msg' => 'Gagal menyimpan pembelian.']);
    //     }
    // }



    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'tanggal' => 'required|date',
            'status' => 'required|in:paid,unpaid,return',
            'deskripsi' => 'nullable|string',
            'biaya_transport' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.gudang_id' => 'required|exists:gudangs,id',
            'items.*.satuan_id' => 'required|exists:satuans,id',
            'items.*.jumlah' => 'required|numeric|min:1',
            'items.*.harga' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // generate nomor faktur otomatis
            $row = DB::table('pembelians')
                ->selectRaw("MAX(CAST(SUBSTRING(no_faktur, 4) AS UNSIGNED)) as max_no")
                ->where('no_faktur', 'like', 'PB-%')
                ->lockForUpdate()
                ->first();

            $next = ($row->max_no ?? 0) + 1;
            $noFaktur = 'PB-' . str_pad($next, 6, '0', STR_PAD_LEFT);

            // simpan pembelian
            $pembelian = Pembelian::create([
                'supplier_id' => $validated['supplier_id'] ?? null,
                'no_faktur' => $noFaktur,
                'tanggal' => $validated['tanggal'],
                'deskripsi' => $validated['deskripsi'] ?? null,
                'biaya_transport' => $validated['biaya_transport'] ?? 0,
                'status' => $validated['status'],
                'sub_total' => collect($validated['items'])->sum(fn($i) => $i['jumlah'] * $i['harga']),
                'total' => collect($validated['items'])->sum(fn($i) => $i['jumlah'] * $i['harga']) + ($validated['biaya_transport'] ?? 0),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            // simpan detail item + update stok
            foreach ($validated['items'] as $it) {
                $last = ItemPembelian::where('item_id', $it['item_id'])
                    ->where('satuan_id', $it['satuan_id'])
                    ->orderByDesc('created_at')
                    ->first();

                $pembelian->items()->create([
                    'item_id' => $it['item_id'],
                    'gudang_id' => $it['gudang_id'],
                    'satuan_id' => $it['satuan_id'],
                    'jumlah' => $it['jumlah'],
                    'harga_sebelumnya' => $last?->harga_beli ?? 0,
                    'harga_beli' => $it['harga'],
                    'total' => $it['jumlah'] * $it['harga'],
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);

                // update stok gudang
                $ig = ItemGudang::firstOrNew([
                    'item_id'   => $it['item_id'],
                    'gudang_id' => $it['gudang_id'],
                    'satuan_id' => $it['satuan_id'],
                ]);
                $ig->stok = ($ig->stok ?? 0) + $it['jumlah'];
                $ig->save();
            }

            DB::commit();

            return redirect()->route('pembelian.index')
                ->with('success', "Pembelian berhasil disimpan! No Faktur: $noFaktur");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function getLastPrice($itemId, Request $request)
    {
        $satuanId = $request->query('satuan_id');

        $last = ItemPembelian::where('item_id', $itemId)
            ->where('satuan_id', $satuanId)
            ->orderByDesc('created_at')
            ->first();

        return response()->json([
            'harga_sebelumnya' => $last?->harga_beli ?? 0
        ]);
    }


    public function show($id)
    {
        $pembelian = Pembelian::with(['items.item', 'items.gudang', 'items.satuan', 'supplier'])
            ->findOrFail($id);

        $suppliers = Supplier::all();

        return view('auth.pembelian.show', compact('pembelian', 'suppliers'));
    }

    public function update(Request $request, $id)
    {
        $pembelian = Pembelian::findOrFail($id);

        $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'tanggal' => 'required|date',
            'deskripsi' => 'nullable|string|max:255',
            'biaya_transport' => 'nullable|numeric',
        ]);

        $pembelian->update([
            'supplier_id' => $request->supplier_id,
            'tanggal' => $request->tanggal,
            'deskripsi' => $request->deskripsi,
            'biaya_transport' => $request->biaya_transport ?? 0,
            'status' => $request->status === 'paid' ? 'paid' : 'unpaid',
        ]);

        return redirect()->route('pembelian.show', $id)
            ->with('success', 'Data pembelian berhasil diperbarui.');
    }
}
