<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Item;

class Satuan extends Model
{
    use SoftDeletes;

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
        'is_base' => 'boolean',
        'harga_retail' => 'decimal:2',
        'partai_kecil' => 'decimal:2',
        'harga_grosir' => 'decimal:2',
    ];

    /**
     * Relasi ke Item
     */
    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
