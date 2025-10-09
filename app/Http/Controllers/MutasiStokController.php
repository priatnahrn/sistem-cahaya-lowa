<?php

namespace App\Http\Controllers;

use App\Models\MutasiStok;
use App\Models\Gudang;
use App\Models\Item;
use App\Models\ItemPembelian;
use Illuminate\Http\Request;

class MutasiStokController extends Controller
{
    /**
     * Halaman daftar mutasi stok
     */
    public function index()
    {
        $mutasis = MutasiStok::with(['gudangAsal', 'gudangTujuan'])
            ->latest()
            ->paginate(10);

        return view('auth.mutasi-stok.index', compact('mutasis'));
    }

    /**
     * Halaman form tambah mutasi stok
     */
    public function create()
    {
        $dateCode = now()->format('dmy');
        $last = MutasiStok::latest()->first();
        $nextNum = $last ? intval(substr($last->no_mutasi, -3)) + 1 : 1;
        $newCode = 'MS' . $dateCode . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

        $gudangs = Gudang::select('id', 'nama_gudang')->get();

        $items = Item::with([
            'satuans:id,nama_satuan',
            'pembelians:id,item_id,gudang_id,satuan_id,jumlah'
        ])->get();


        $items->each(function ($item) {
            $item->stok_data = $item->pembelians->map(function ($p) {
                return [
                    'gudang_id' => $p->gudang_id,
                    'satuan_id' => $p->satuan_id,
                    'stok' => $p->jumlah,
                ];
            });
        });


        return view('auth.mutasi-stok.create', compact('newCode', 'gudangs', 'items'));
    }



    /**
     * Simpan mutasi stok baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'no_mutasi' => 'required|unique:mutasi_stoks,no_mutasi',
            'tanggal_mutasi' => 'required|date',
            'gudang_asal_id' => 'required|exists:gudangs,id',
            'gudang_tujuan_id' => 'required|exists:gudangs,id|different:gudang_asal_id',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.satuan_id' => 'required|exists:satuans,id',
            'items.*.jumlah' => 'required|numeric|min:0.01',
        ]);

        $mutasi = MutasiStok::create([
            'no_mutasi' => $request->no_mutasi,
            'tanggal_mutasi' => $request->tanggal_mutasi,
            'gudang_asal_id' => $request->gudang_asal_id,
            'gudang_tujuan_id' => $request->gudang_tujuan_id,
            'keterangan' => $request->keterangan,
        ]);

        foreach ($request->items as $row) {
            $mutasi->items()->create([
                'item_id' => $row['item_id'],
                'satuan_id' => $row['satuan_id'],
                'jumlah' => $row['jumlah'],
            ]);
        }

        return redirect()->route('mutasi-stok.index')->with('success', 'Mutasi stok berhasil ditambahkan.');
    }
}
