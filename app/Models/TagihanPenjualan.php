<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TagihanPenjualan extends Model
{
     use HasFactory;

    protected $table = 'tagihan_penjualans';

    protected $fillable = [
        'penjualan_id',
        'no_tagihan',
        'tanggal_tagihan',
        'total',
        'jumlah_bayar',
        'sisa',
        'status_tagihan',
        'catatan',
        'created_by',
        'updated_by',
    ];

    // 🔁 Relasi ke Penjualan
    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class, 'penjualan_id');
    }

    // 💰 Relasi ke Pembayaran (melalui penjualan)
    public function pembayarans()
    {
        return $this->hasMany(Pembayaran::class, 'penjualan_id', 'penjualan_id');
    }

    // 👤 Relasi ke User pembuat
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
