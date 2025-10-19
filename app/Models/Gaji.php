<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gaji extends Model
{
    protected $fillable = [
        'nama_karyawan',
        'tanggal',
        'upah_harian',
        'utang',
        'saldo',
        'keterangan'
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'upah_harian' => 'decimal:2',
        'utang' => 'decimal:2',
        'saldo' => 'decimal:2'
    ];
}