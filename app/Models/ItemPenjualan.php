<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemPenjualan extends Model
{
    protected $table = 'item_penjualans';

    protected $fillable = [
        'penjualan_id',
        'item_id',
        'gudang_id',
        'satuan_id',
        'jumlah',
        'harga',
        'total',
        'keterangan',
        'created_by',
        'updated_by',
    ];

    // 🔹 Relasi ke penjualan utama
    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class, 'penjualan_id', 'id');
    }

    // 🔹 Relasi ke item/barang
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    // 🔹 Relasi ke gudang tempat item diambil
    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'gudang_id', 'id');
    }

    // 🔹 Relasi ke satuan (misal: pcs, dus)
    public function satuan()
    {
        return $this->belongsTo(Satuan::class, 'satuan_id', 'id');
    }

    // 🔹 User yang membuat data
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    // 🔹 User yang terakhir mengubah data
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }
}
