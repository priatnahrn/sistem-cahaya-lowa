<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pelanggan extends Model
{
    protected $table = 'pelanggans';
    protected $fillable = [
        'nama_pelanggan',
        'kontak',
        'alamat',
    ];

    public function penjualans()
    {
        return $this->hasMany(Penjualan::class);
    }
}
