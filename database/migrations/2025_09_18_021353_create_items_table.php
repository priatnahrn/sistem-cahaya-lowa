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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('kode_item', 50)->unique();  
            $table->string('barcode')->unique();
            $table->string('barcode_path')->nullable();         
            $table->string('nama_item');               
            $table->foreignId('kategori_item_id')
                ->constrained('kategori_items')
                ->onDelete('cascade');                 
            $table->string('foto_path')->nullable();    
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
