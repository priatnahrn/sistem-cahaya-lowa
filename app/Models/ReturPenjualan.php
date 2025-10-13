<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReturPenjualan extends Model
{
    use SoftDeletes;

    protected $table = 'retur_penjualans';

    protected $fillable = [
        'penjualan_id',
        'no_retur',
        'tanggal',
        'catatan',
        'total',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
    ];

    // Relasi ke penjualan
    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class);
    }

    // Relasi ke detail retur
    public function items()
    {
        return $this->hasMany(ItemReturPenjualan::class, 'retur_penjualan_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // accessor status label
    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            'pending' => 'Barang Masih Ada',
            'taken'   => 'Barang Diambil Customer',
            'refund'  => 'Pengembalian Uang Selesai',
            default   => ucfirst($this->status),
        };
    }

    public function getStatusBadgeClassAttribute()
    {
        return match ($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-700',
            'taken'   => 'bg-blue-100 text-blue-700',
            'refund'  => 'bg-green-100 text-green-700',
            default   => 'bg-gray-100 text-gray-700',
        };
    }
}
