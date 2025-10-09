<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MutasiStok extends Model
{
    use HasFactory;

    protected $fillable = [
        'no_mutasi',
        'tanggal_mutasi',
        'gudang_asal_id',
        'gudang_tujuan_id',
        'keterangan',
    ];

    // Relasi ke tabel gudang (asal & tujuan)
    public function gudangAsal()
    {
        return $this->belongsTo(Gudang::class, 'gudang_asal_id');
    }

    public function gudangTujuan()
    {
        return $this->belongsTo(Gudang::class, 'gudang_tujuan_id');
    }

    // Relasi ke item mutasi
    public function items()
    {
        return $this->hasMany(MutasiStokItem::class, 'mutasi_stok_id');
    }

    
}

