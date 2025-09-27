<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penjualan extends Model
{
    protected $fillable = [
        'no_faktur',
        'tanggal',
        'pelanggan_id',
        'deskripsi',
        'sub_total',
        'biaya_transport',
        'total',
        'status_bayar',
        'status_kirim',
        'created_by',
        'updated_by',
    ];

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class);
    }

    public function itemPenjualans()
    {
        return $this->hasMany(ItemPenjualan::class);
    }

}
