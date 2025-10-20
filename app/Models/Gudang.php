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
        'created_by',
        'updated_by',
    ];

    public function items()
    {
        return $this->hasMany(ItemGudang::class, 'gudang_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}