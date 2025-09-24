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
            $table->id(); // bigIncrements -> bigint unsigned
            $table->string('kode_item')->nullable()->unique();
            $table->string('nama_item')->nullable();
            $table->foreignId('kategori_item_id')->nullable()->constrained('kategori_items')->nullOnDelete();
            $table->integer('stok_minimal')->default(0);
            $table->unsignedBigInteger('primary_satuan_id')->nullable(); // nanti FK optional
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
