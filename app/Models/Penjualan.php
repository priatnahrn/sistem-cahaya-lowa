<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penjualan extends Model
{
    protected $table = 'penjualans';

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
        'is_draft',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'datetime',  // otomatis ke Carbon
        'sub_total' => 'decimal:2',
        'biaya_transport' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // 🔹 Relasi ke detail item penjualan
    public function items()
    {
        return $this->hasMany(ItemPenjualan::class, 'penjualan_id', 'id');
    }

    // 🔹 Relasi ke pelanggan
    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'pelanggan_id');
    }

    // 🔹 User yang membuat data
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // 🔹 User yang terakhir mengubah data
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function pembayarans()
    {
        return $this->hasMany(Pembayaran::class, 'penjualan_id');
    }
}
