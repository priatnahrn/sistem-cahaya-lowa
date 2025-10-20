<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\KategoriItem;
use App\Models\Satuan;

class Item extends Model
{
    use SoftDeletes;

    protected $table = 'items';
    protected $fillable = [
        'kode_item',
        'barcode',
        'barcode_path',
        'nama_item',
        'stok_minimal',
        'kategori_item_id',
        'foto_path',
        'created_by',
        'updated_by',
    ];


    /**
     * Relasi ke kategori item
     */
    public function kategori()
    {
        return $this->belongsTo(KategoriItem::class, 'kategori_item_id');
    }

    /**
     * Relasi ke semua satuan milik item
     */
    public function satuans()
    {
        // urutkan: base dulu, lalu berdasarkan id
        return $this->hasMany(Satuan::class)
            ->orderBy('is_base', 'desc')
            ->orderBy('id', 'asc');
    }

    /**
     * Relasi ke primary satuan (satuan dasar)
     */
    public function primarySatuan()
    {
        return $this->hasOne(Satuan::class)->where('is_base', true);
    }

    public function gudangItems()
    {
        return $this->hasMany(ItemGudang::class, 'item_id');
    }

    public function penjualans()
    {
        return $this->hasMany(ItemPenjualan::class, 'item_id');
    }

    public function itemGudangs()
    {
        return $this->hasMany(ItemGudang::class, 'item_id');
    }


    // App\Models\Item.php
    public function pembelians()
    {
        return $this->hasMany(ItemPembelian::class);
    }

    public function stok_data()
    {
        return $this->hasMany(ItemGudang::class, 'item_id')
            ->select(['item_id', 'gudang_id', 'satuan_id', 'stok']); // hanya ambil kolom penting
    }

    public function kategoriItem()
    {
        return $this->belongsTo(KategoriItem::class, 'kategori_item_id');
    }
}
