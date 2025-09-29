<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemReturPembelian extends Model
{
    use SoftDeletes;

    protected $table = 'item_retur_pembelians';

    protected $fillable = [
        'retur_pembelian_id',
        'item_pembelian_id',
        'jumlah',
        'harga',
        'sub_total',
    ];

    // 🔗 Relasi ke retur header
    public function retur()
    {
        return $this->belongsTo(ReturPembelian::class, 'retur_pembelian_id');
    }

    // 🔗 Relasi ke item pembelian
    public function itemPembelian()
    {
        return $this->belongsTo(ItemPembelian::class, 'item_pembelian_id');
    }

    // 🔗 Shortcut ke item (lewat item_pembelian)
    public function item()
    {
        return $this->hasOneThrough(
            Item::class,
            ItemPembelian::class,
            'id',        // Foreign key di item_pembelians
            'id',        // Foreign key di items
            'item_pembelian_id', // Local key di item_retur_pembelians
            'item_id'    // Local key di item_pembelians
        );
    }
}
