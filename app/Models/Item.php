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
        'nama_item',
        'kategori_item_id',
        'stok_minimal',
        'primary_satuan_id',
        'foto_path',
    ];

    /**
     * Relasi ke Kategori
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
        return $this->hasMany(Satuan::class);
    }

    /**
     * Relasi ke primary satuan (unit utama)
     */
    public function primarySatuan()
    {
        return $this->belongsTo(Satuan::class, 'primary_satuan_id');
    }
}
