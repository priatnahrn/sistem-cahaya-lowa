<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produksi extends Model
{
    use HasFactory;

    protected $fillable = [
        'penjualan_id',
        'no_produksi',
        'status',
        'tanggal_mulai',
        'tanggal_selesai',
        'keterangan',
        'created_by',
        'updated_by',
    ];

    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class);
    }

    public function items()
    {
        return $this->hasMany(ItemProduksi::class);
    }
}
