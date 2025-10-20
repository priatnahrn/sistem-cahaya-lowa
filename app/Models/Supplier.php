<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $table = 'suppliers';
    
    protected $fillable = [
        'nama_supplier',
        'kontak',
        'alamat',
        'nama_bank',
        'nomor_rekening',
        'atas_nama',
        'created_by',
        'updated_by',
    ];

    /**
     * Relasi ke transaksi pembelian
     */
    public function pembelians()
    {
        return $this->hasMany(Pembelian::class, 'supplier_id');
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