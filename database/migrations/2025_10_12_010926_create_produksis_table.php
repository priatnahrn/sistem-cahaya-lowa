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
        // ===== Tabel produksis =====
        Schema::create('produksis', function (Blueprint $table) {
            $table->id();

            $table->foreignId('penjualan_id')
                ->constrained('penjualans')
                ->onDelete('cascade');

            $table->string('no_produksi', 50)->unique();
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->text('keterangan')->nullable();
            $table->dateTime('tanggal_mulai')->nullable();
            $table->dateTime('tanggal_selesai')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });

        // ===== Tabel item_produksis =====
        Schema::create('item_produksis', function (Blueprint $table) {
            $table->id();

            $table->foreignId('produksi_id')
                ->constrained('produksis')
                ->onDelete('cascade');

            $table->foreignId('item_id')
                ->constrained('items')
                ->onDelete('cascade');

            $table->foreignId('item_penjualan_id')
                ->nullable()
                ->constrained('item_penjualans')
                ->onDelete('set null');

            $table->decimal('jumlah_dibutuhkan', 15, 2);
            $table->decimal('jumlah_selesai', 15, 2)->default(0);
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->text('catatan')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Hapus tabel dengan urutan terbalik untuk hindari foreign key error
        Schema::dropIfExists('item_produksis');
        Schema::dropIfExists('produksis');
    }
};
