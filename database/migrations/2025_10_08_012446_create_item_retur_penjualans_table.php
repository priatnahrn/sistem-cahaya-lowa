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
        Schema::create('item_retur_penjualans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retur_penjualan_id')->constrained('retur_penjualans')->cascadeOnDelete();
            $table->foreignId('item_penjualan_id')->constrained('item_penjualans')->cascadeOnDelete();
            $table->decimal('jumlah', 16, 4)->default(0);
            $table->decimal('harga', 16, 4)->default(0);
            $table->decimal('sub_total', 16, 4)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_retur_penjualans');
    }
};
