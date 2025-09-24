<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pembelian extends Model
{
    use HasFactory;

    protected $table = 'pembelians';

    protected $fillable = [
        'supplier_id',
        'no_faktur',
        'tanggal',
        'deskripsi',
        'sub_total',
        'biaya_transport',
        'total',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'sub_total' => 'decimal:2',
        'biaya_transport' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // -------------------------------
    // Relationships
    // -------------------------------

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(ItemPembelian::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }


    protected static function boot()
    {
        parent::boot();

        static::updating(function ($model) {
            if ($model->isDirty('no_faktur')) {
                throw new \Exception("No Faktur tidak boleh diubah setelah dibuat.");
            }
        });
    }
}
