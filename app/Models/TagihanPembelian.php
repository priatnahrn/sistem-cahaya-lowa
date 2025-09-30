<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TagihanPembelian extends Model
{
    protected $fillable = [
        'pembelian_id',
        'no_tagihan',
        'tanggal',
        'jumlah_bayar',
        'sisa',
        'total',
        'catatan',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
    ];

    public function pembelian()
    {
        return $this->belongsTo(Pembelian::class);
    }

    // Ambil supplier langsung lewat pembelian
    public function supplier()
    {
        return $this->pembelian->supplier;
    }
}
