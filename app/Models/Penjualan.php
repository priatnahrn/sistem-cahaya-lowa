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
        'mode',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'datetime',  // ← Ini akan auto-convert ke Carbon
        'sub_total' => 'decimal:2',
        'biaya_transport' => 'decimal:2',
        'total' => 'decimal:2',
    ];
    public function items()
    {
        return $this->hasMany(ItemPenjualan::class, 'penjualan_id');
    }

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class);
    }

    public function itemPenjualans()
    {
        return $this->hasMany(ItemPenjualan::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
