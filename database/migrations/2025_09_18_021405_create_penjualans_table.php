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
            $table->foreignId('pelanggan_id')->nullable()->constrained('pelanggans')->onDelete('cascade');
            $table->text('deskripsi')->nullable();
            $table->string('harga_jual')->nullable();
            $table->decimal('sub_total', 15, 2);
            $table->decimal('biaya_transport', 15, 2)->default(0);
            $table->decimal('total', 15, 2);
            $table->enum('status_bayar', ['paid', 'unpaid', 'return'])->default('unpaid');
            $table->enum('mode', ['ambil', 'antar'])->default('ambil');
            $table->boolean('is_draft')->default(false);
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
