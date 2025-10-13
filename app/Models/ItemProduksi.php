<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemProduksi extends Model
{
    use HasFactory;

    protected $fillable = [
        'produksi_id',
        'item_id',
        'item_penjualan_id',
        'jumlah_dibutuhkan',
        'jumlah_selesai',
        'status',
        'catatan'
    ];

    public function produksi()
    {
        return $this->belongsTo(Produksi::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function satuan()
    {
        return $this->belongsTo(Satuan::class, 'satuan_id');
    }

    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }

    public function itemPenjualan()
    {
        return $this->belongsTo(ItemPenjualan::class);
    }
}
