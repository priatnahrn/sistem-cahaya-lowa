<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MutasiStokItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'mutasi_stok_id',
        'item_id',
        'satuan_id',
        'jumlah',
    ];

    public function mutasiStok()
    {
        return $this->belongsTo(MutasiStok::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function satuan()
    {
        return $this->belongsTo(Satuan::class);
    }

    
}
