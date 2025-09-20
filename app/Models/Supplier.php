<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $table = 'suppliers';
    protected $fillable = [
        'nama_supplier',
        'kontak',
        'alamat',
        'nama_bank',
        'nomor_rekening',
    ];

    public function items()
    {
        return $this->hasMany(Item::class);
    }
    
}
