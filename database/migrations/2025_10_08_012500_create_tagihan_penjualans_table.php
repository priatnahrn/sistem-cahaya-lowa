<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tagihan_penjualans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penjualan_id')->constrained()->onDelete('cascade');
            $table->string('no_tagihan')->unique(); // misal: TG081025001
            $table->dateTime('tanggal_tagihan');
            $table->decimal('total', 12, 2); // total nominal penjualan
            $table->decimal('jumlah_bayar', 12, 2)->default(0); // total yang sudah dibayar
            $table->decimal('sisa', 12, 2)->default(0); // otomatis total - jumlah_bayar
            $table->enum('status_tagihan', ['belum_lunas', 'lunas'])->default('belum_lunas');
            $table->string('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tagihan_penjualans');
    }
};
