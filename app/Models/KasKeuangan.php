<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KasKeuangan extends Model
{
    protected $table = 'kas_keuangans';

    protected $fillable = [
        'user_id',
        'penjualans_id',
        'pembelians_id',
        'pembayarans_id',
        'tagihan_penjualans_id',
        'tagihan_pembelians_id',
        'keterangan',
        'jenis',
        'nominal',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class, 'penjualans_id');
    }

    public function pembelian()
    {
        return $this->belongsTo(Pembelian::class, 'pembelians_id');
    }

    public function pembayaran()
    {
        return $this->belongsTo(Pembayaran::class, 'pembayarans_id');
    }

    public function tagihanPenjualan()
    {
        return $this->belongsTo(TagihanPenjualan::class, 'tagihan_penjualans_id');
    }


    public function tagihanPembelian()
    {
        return $this->belongsTo(TagihanPembelian::class, 'tagihan_pembelians_id');
    }



}
