<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KategoriItem extends Model
{
    protected $table = 'kategori_items';
    
    protected $fillable = [
        'nama_kategori',
        'created_by',
        'updated_by',
    ];


    public function items()
    {
        return $this->hasMany(Item::class);
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
