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
        Schema::create('penjualans', function (Blueprint $table) {
            $table->id();
            $table->string('no_faktur')->unique();
            $table->dateTime('tanggal');
            $table->foreignId('pelanggan_id')->constrained('pelanggans')->onDelete('cascade');
            $table->text('deskripsi')->nullable();
            $table->decimal('sub_total', 15, 2);
            $table->decimal('biaya_transport', 15, 2)->default(0);
            $table->decimal('total', 15, 2);
            $table->enum('status_bayar', ['lunas', 'belum lunas'])->default('belum lunas');
            $table->enum('status_kirim', ['-', 'perlu dikirim' ,'dalam pengiriman', 'diterima'])->default('-');
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
        Schema::dropIfExists('penjualans');
    }
};
