<?php

namespace App\Http\Controllers;

use App\Models\TagihanPembelian;
use Illuminate\Http\Request;


class TagihanPembelianController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tagihans = TagihanPembelian::with('pembelian.supplier')
            ->latest()
            ->get();

        $tagihansJson = $tagihans->map(fn($t) => [
            'id'         => $t->id,
            'no_tagihan' => $t->no_tagihan,
            'no_faktur'  => $t->pembelian->no_faktur,
            'supplier'   => $t->pembelian->supplier->nama_supplier ?? '-',
            'tanggal'    => $t->tanggal->format('d/m/Y H:i'),
            'total'      => 'Rp ' . number_format($t->total, 0, ',', '.'),
            'url'        => route('tagihan.pembelian.show', $t->id),
            'can_edit'   => $t->sisa > 0,
            'can_delete' => $t->sisa > 0,
        ])->toArray();

        return view('auth.pembelian.tagihan.index', compact('tagihansJson'));
    }



    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Ambil data tagihan beserta relasi pembelian, supplier, dan item
        $tagihan = TagihanPembelian::with([
            'pembelian.supplier',
            'pembelian.items.item',
            'pembelian.items.gudang',
            'pembelian.items.satuan',
        ])->findOrFail($id);

        return view('auth.pembelian.tagihan.show', compact('tagihan'));
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
