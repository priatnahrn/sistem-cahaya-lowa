<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pembayaran extends Model
{
    use HasFactory;

    protected $table = 'pembayarans';

    protected $fillable = [
        'penjualan_id',
        'tanggal',
        'jumlah_bayar',
        'sisa',
        'method',
        'keterangan',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'jumlah_bayar' => 'decimal:2',
        'sisa' => 'decimal:2',
    ];

    /**
     * Boot method untuk auto-update TagihanPenjualan
     */
    protected static function boot()
    {
        parent::boot();

        // Setelah pembayaran dibuat, update tagihan
        static::created(function ($pembayaran) {
            $pembayaran->updateTagihan();
        });

        // Setelah pembayaran dihapus, recalculate tagihan
        static::deleted(function ($pembayaran) {
            $pembayaran->recalculateTagihan();
        });
    }

    // 🔁 Relasi ke Penjualan
    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class, 'penjualan_id');
    }

    // 🔁 Relasi ke User (kasir atau pembuat pembayaran)
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // 💳 Accessor untuk format method pembayaran
    public function getMethodLabelAttribute()
    {
        return match ($this->method) {
            'cash' => 'Tunai',
            'transfer' => 'Transfer Bank',
            'qris' => 'QRIS',
            'wallet' => 'Dompet Digital',
            default => ucfirst($this->method),
        };
    }

    /**
     * Update TagihanPenjualan setelah pembayaran
     */
    public function updateTagihan()
    {
        $tagihan = TagihanPenjualan::where('penjualan_id', $this->penjualan_id)->first();
        
        if (!$tagihan) {
            return;
        }

        // Hitung total pembayaran (exclude yang negatif/pengembalian)
        $totalBayar = Pembayaran::where('penjualan_id', $this->penjualan_id)
            ->where('jumlah_bayar', '>', 0)
            ->sum('jumlah_bayar');

        $sisa = max(0, $tagihan->total - $totalBayar);
        $isLunas = $sisa <= 0;

        // Generate catatan pembayaran
        $catatan = $this->generateCatatanPembayaran();

        // Update tagihan
        $tagihan->jumlah_bayar = $totalBayar;
        $tagihan->sisa = $sisa;
        $tagihan->status_tagihan = $isLunas ? 'lunas' : 'belum_lunas';
        
        // Append catatan
        if ($catatan) {
            $tagihan->catatan = $tagihan->catatan 
                ? $tagihan->catatan . "\n" . $catatan 
                : $catatan;
        }
        
        $tagihan->updated_by = $this->created_by;
        $tagihan->save();
    }

    /**
     * Recalculate tagihan setelah pembayaran dihapus
     */
    public function recalculateTagihan()
    {
        $tagihan = TagihanPenjualan::where('penjualan_id', $this->penjualan_id)->first();
        
        if (!$tagihan) {
            return;
        }

        // Hitung ulang total pembayaran
        $totalBayar = Pembayaran::where('penjualan_id', $this->penjualan_id)
            ->where('jumlah_bayar', '>', 0)
            ->sum('jumlah_bayar');

        $sisa = max(0, $tagihan->total - $totalBayar);
        $isLunas = $sisa <= 0;

        $tagihan->jumlah_bayar = $totalBayar;
        $tagihan->sisa = $sisa;
        $tagihan->status_tagihan = $isLunas ? 'lunas' : 'belum_lunas';
        $tagihan->save();
    }

    /**
     * Generate catatan pembayaran otomatis
     */
    private function generateCatatanPembayaran(): string
    {
        $metode = $this->method_label;
        $nominal = number_format($this->jumlah_bayar, 0, ',', '.');
        $tanggal = $this->tanggal->timezone('Asia/Makassar')->format('d/m/Y H:i');
        
        if ($this->jumlah_bayar < 0) {
            return "Pengembalian dana sebesar Rp {$nominal} pada {$tanggal}";
        }
        
        $catatan = "Pembayaran {$metode} sebesar Rp {$nominal} pada {$tanggal}";
        
        if ($this->keterangan) {
            $catatan .= " - {$this->keterangan}";
        }
        
        return $catatan;
    }
}