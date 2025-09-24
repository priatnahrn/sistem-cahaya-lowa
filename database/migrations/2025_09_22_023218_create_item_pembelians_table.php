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
         // --- purchase_items ---
        Schema::create('item_pembelians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pembelian_id')->constrained('pembelians')->cascadeOnDelete();

            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();

            $table->foreignId('gudang_id')->nullable()->constrained('gudangs')->nullOnDelete();
            $table->foreignId('satuan_id')->nullable()->constrained('satuans')->nullOnDelete();

            $table->decimal('jumlah', 16, 4)->default(0);
            $table->decimal('harga_sebelumnya', 18, 2)->nullable()->default(0);
            $table->decimal('harga_beli', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);

            $table->timestamps();
            $table->index(['pembelian_id', 'item_id']);
        });
    }
    
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_pembelians');
    }
};
