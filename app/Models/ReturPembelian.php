<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReturPembelian extends Model
{
    use SoftDeletes;

    protected $table = 'retur_pembelians';

    protected $fillable = [
        'pembelian_id',
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

    // 🔗 Relasi ke pembelian
    public function pembelian()
    {
        return $this->belongsTo(Pembelian::class);
    }

    // 🔗 Relasi ke detail retur
    public function items()
    {
        return $this->hasMany(ItemReturPembelian::class);
    }

    // 🔗 Relasi ke user pembuat
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // 🔗 Relasi ke user update
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // 🏷️ Accessor untuk status label
    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            'pending' => 'Barang Masih Ada',
            'taken'   => 'Barang Diambil Sales',
            'refund'  => 'Pengembalian Uang Selesai',
            default   => ucfirst($this->status),
        };
    }

    // 🏷️ Accessor untuk badge warna (opsional)
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