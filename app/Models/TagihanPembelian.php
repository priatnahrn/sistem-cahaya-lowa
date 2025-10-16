<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TagihanPembelian extends Model
{
    protected $fillable = [
        'pembelian_id',
        'no_tagihan',
        'tanggal',
        'jumlah_bayar',
        'sisa',
        'total',
        'catatan',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'jumlah_bayar' => 'decimal:2',
        'sisa' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    protected $attributes = [
        'jumlah_bayar' => 0,
    ];

    /**
     * Relasi ke pembelian
     */
    public function pembelian(): BelongsTo
    {
        return $this->belongsTo(Pembelian::class);
    }

    /**
     * Relasi ke user yang membuat (creator)
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke user yang mengupdate (updater)
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Accessor untuk mendapatkan supplier langsung
     */
    public function getSupplierAttribute()
    {
        return $this->pembelian?->supplier;
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
     * Scope untuk filter berdasarkan supplier
     */
    public function scopeBySupplier($query, $supplierId)
    {
        return $query->whereHas('pembelian', function ($q) use ($supplierId) {
            $q->where('supplier_id', $supplierId);
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
        $prefix = 'TGB';
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