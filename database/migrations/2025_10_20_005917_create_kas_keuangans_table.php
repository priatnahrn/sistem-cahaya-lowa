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
        Schema::create('kas_keuangans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('penjualans_id')->nullable()->constrained('penjualans')->onDelete('cascade');
            $table->foreignId('pembelians_id')->nullable()->constrained('pembelians')->onDelete('cascade');
            $table->foreignId('pembayarans_id')->nullable()->constrained('pembayarans')->onDelete('cascade');
            $table->foreignId('tagihan_penjualans_id')->nullable()->constrained('tagihan_penjualans')->onDelete('cascade');
            $table->foreignId('tagihan_pembelians_id')->nullable()->constrained('tagihan_pembelians')->onDelete('cascade');
            $table->string('keterangan')->nullable();
            $table->enum('jenis', ['masuk', 'keluar'])->nullable();
            $table->decimal('nominal', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kas_keuangans');
    }
};
