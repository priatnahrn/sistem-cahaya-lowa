<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemPembelian extends Model
{
    use HasFactory;

    protected $table = 'item_pembelians';

    protected $fillable = [
        'pembelian_id',
        'item_id',
        'gudang_id',
        'satuan_id',
        'jumlah',
        'harga_sebelumnya',
        'harga_beli',
        'total',
    ];

    protected $casts = [
        'jumlah' => 'decimal:4',
        'harga_sebelumnya' => 'decimal:2',
        'harga_beli' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // -------------------------------
    // Relationships
    // -------------------------------
    public function pembelian()
    {
        return $this->belongsTo(Pembelian::class);
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
}
