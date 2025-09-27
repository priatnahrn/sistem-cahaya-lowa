<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemPenjualan extends Model
{
    protected $table = 'item_penjualans';
    protected $fillable = [
        'penjualan_id',
        'item_id',
        'gudang_id',
        'satuan_id',
        'jumlah',
        'harga',
        'total',
        'keterangan',
        'created_by',
        'updated_by',
    ];

    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }

    public function satuan()
    {
        return $this->belongsTo(Satuan::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    
}
