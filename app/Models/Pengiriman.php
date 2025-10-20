<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengiriman extends Model
{

    protected $table = 'pengirimen';
    
    protected $fillable = [
        'penjualan_id',
        'no_pengiriman',
        'tanggal_pengiriman',
        'status_pengiriman',
        'created_by',
        'updated_by',
        'supir'
    ];

    // Relasi ke Penjualan
    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class);
    }

    // Relasi ke User (pembuat pengiriman)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relasi ke User (pembuat pembaruan terakhir)
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
