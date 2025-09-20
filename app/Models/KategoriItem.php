<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KategoriItem extends Model
{
    protected $table = 'kategori_items';
    
    protected $fillable = [
        'nama_kategori',
        'deskripsi',
    ];


    public function items()
    {
        return $this->hasMany(Item::class);
    }
    


}
