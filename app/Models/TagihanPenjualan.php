<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TagihanPenjualan extends Model
{
    use HasFactory;

    protected $table = 'tagihan_penjualans';

    protected $fillable = [
        'penjualan_id',
        'no_tagihan',
        'tanggal_tagihan',
        'total',
        'jumlah_bayar',
        'sisa',
        'status_tagihan',
        'catatan',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal_tagihan' => 'datetime',
        'total' => 'decimal:2',
        'jumlah_bayar' => 'decimal:2',
        'sisa' => 'decimal:2',
    ];

    protected $attributes = [
        'jumlah_bayar' => 0,
    ];

    /**
     * Relasi ke Penjualan
     */
    public function penjualan(): BelongsTo
    {
        return $this->belongsTo(Penjualan::class, 'penjualan_id');
    }

    /**
     * Relasi ke User pembuat
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke User pengupdate
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Accessor untuk mendapatkan pelanggan langsung
     */
    public function getPelangganAttribute()
    {
        return $this->penjualan?->pelanggan;
    }

    /**
     * Accessor untuk cek apakah sudah lunas
     */
    public function getIsLunasAttribute(): bool
    {
        return $this->sisa <= 0;
    }

    /**
     * Accessor untuk persentase pembayaran
     */
    public function getPersentaseBayarAttribute(): float
    {
        if ($this->total <= 0) {
            return 0;
        }
        return round(($this->jumlah_bayar / $this->total) * 100, 2);
    }

    /**
     * Scope untuk filter tagihan yang belum lunas
     */
    public function scopeBelumLunas($query)
    {
        return $query->where('sisa', '>', 0);
    }

    /**
     * Scope untuk filter tagihan yang sudah lunas
     */
    public function scopeSudahLunas($query)
    {
        return $query->where('sisa', '<=', 0);
    }

    /**
     * Scope untuk filter berdasarkan pelanggan
     */
    public function scopeByPelanggan($query, $pelangganId)
    {
        return $query->whereHas('penjualan', function ($q) use ($pelangganId) {
            $q->where('pelanggan_id', $pelangganId);
        });
    }

    /**
     * Boot method untuk auto-generate nomor tagihan
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tagihan) {
            if (empty($tagihan->no_tagihan)) {
                $tagihan->no_tagihan = self::generateNoTagihan();
            }
            
            // Set sisa = total jika belum diset
            if (is_null($tagihan->sisa)) {
                $tagihan->sisa = $tagihan->total;
            }
        });
    }

    /**
     * Generate nomor tagihan otomatis
     */
    public static function generateNoTagihan(): string
    {
        $prefix = 'TGJ'; // Tagihan Penjualan
        $date = date('ymd'); // Format: YYMMDD
        
        // Ambil nomor urut terakhir hari ini
        $lastTagihan = self::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastTagihan ? 
            intval(substr($lastTagihan->no_tagihan, -3)) + 1 : 1;
        
        return sprintf('%s-%s-%03d', $prefix, $date, $sequence);
    }

    /**
     * Method untuk proses pembayaran (cicilan atau pelunasan)
     */
    public function bayar(float $jumlah, ?string $catatan = null): bool
    {
        if ($jumlah <= 0 || $jumlah > $this->sisa) {
            return false;
        }

        $this->jumlah_bayar += $jumlah;
        $this->sisa -= $jumlah;
        
        if ($catatan) {
            // Append catatan baru ke catatan yang sudah ada
            if ($this->catatan) {
                $this->catatan .= "\n" . $catatan;
            } else {
                $this->catatan = $catatan;
            }
        }

        return $this->save();
    }
}