<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pembayaran extends Model
{
    use HasFactory;

    protected $table = 'pembayarans';

    protected $fillable = [
        'penjualan_id',
        'tanggal',
        'jumlah_bayar',
        'sisa',
        'method',
        'keterangan',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'jumlah_bayar' => 'decimal:2',
        'sisa' => 'decimal:2',
    ];

    // 🔁 Relasi ke Penjualan
    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class, 'penjualan_id');
    }

    // 🔁 Relasi ke User (kasir atau pembuat pembayaran)
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // 💳 Accessor untuk format method pembayaran
    public function getMethodLabelAttribute()
    {
        return match ($this->method) {
            'cash' => 'Tunai',
            'transfer' => 'Transfer Bank',
            'qris' => 'QRIS',
            'wallet' => 'Dompet Digital',
            default => ucfirst($this->method),
        };
    }


    
}
