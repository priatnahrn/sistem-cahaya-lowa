<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemGudang extends Model
{
    protected $table = 'item_gudangs';

    protected $fillable = [
        'item_id',
        'gudang_id',
        'stok_minimal',
        'stok',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }

}
