<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Item;

class Satuan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'satuans';

    protected $fillable = [
        'item_id',
        'nama_satuan',
        'jumlah',
        'is_base',
        'harga_retail',
        'partai_kecil',
        'harga_grosir',
    ];

    protected $casts = [
        'jumlah'        => 'integer',
        'is_base'       => 'boolean',
        'harga_retail'  => 'decimal:2',
        'partai_kecil'  => 'decimal:2',
        'harga_grosir'  => 'decimal:2',
        'deleted_at'    => 'datetime',
    ];

    /**
     * Relasi ke Item
     */
    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
