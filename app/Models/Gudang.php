<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gudang extends Model
{
    protected $table = 'gudangs';
    
    protected $fillable = [
        'kode_gudang',
        'nama_gudang',
        'lokasi',
    ];

    public function items()
    {
        return $this->hasMany(Item::class);
    }
}
