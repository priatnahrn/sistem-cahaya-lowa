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
        Schema::create('satuans', function (Blueprint $table) {
            $table->id(); // bigIncrements
            $table->unsignedBigInteger('item_id'); // cocok dengan items.id (bigint)
            $table->string('nama_satuan');
            $table->integer('jumlah')->default(1);
            $table->boolean('is_base')->default(false);
            $table->decimal('harga_retail', 15, 2)->nullable();
            $table->decimal('partai_kecil', 15, 2)->nullable();
            $table->decimal('harga_grosir', 15, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['item_id', 'nama_satuan']);
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('satuans');
    }
};
