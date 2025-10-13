<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemReturPenjualan extends Model
{
    use SoftDeletes;

    protected $table = 'item_retur_penjualans';

    protected $fillable = [
        'retur_penjualan_id',
        'item_penjualan_id',
        'jumlah',
        'harga',
        'sub_total',
    ];

    // relasi ke header retur
    public function retur()
    {
        return $this->belongsTo(ReturPenjualan::class, 'retur_penjualan_id');
    }

    // relasi ke item_penjualan (detail penjualan)
    public function itemPenjualan()
    {
        return $this->belongsTo(ItemPenjualan::class, 'item_penjualan_id');
    }

    // shortcut ke Item lewat ItemPenjualan (mirip pattern di ItemReturPembelian)
    public function item()
    {
        return $this->hasOneThrough(
            Item::class,              // final model
            ItemPenjualan::class,     // intermediate model
            'id',                     // foreign key on item_penjualans (we will override by local keys below)
            'id',                     // foreign key on items
            'item_penjualan_id',      // local key on this table (item_retur_penjualans)
            'item_id'                 // local key on item_penjualans
        );
    }
}
