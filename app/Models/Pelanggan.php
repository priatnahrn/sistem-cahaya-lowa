<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pelanggan extends Model
{
    protected $table = 'pelanggans';
    
    protected $fillable = [
        'nama_pelanggan',
        'kontak',
        'alamat',
        'level',
        'created_by',
        'updated_by',
    ];

    /**
     * Relasi ke transaksi penjualan
     */
    public function penjualans()
    {
        return $this->hasMany(Penjualan::class, 'pelanggan_id');
    }

    /**
     * Relasi ke user creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke user updater
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}