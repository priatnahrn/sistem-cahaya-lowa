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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('nama_supplier');
            $table->string('kontak')->nullable();
            $table->text('alamat')->nullable();
            $table->enum('nama_bank', ['BCA', 'BNI', 'BRI', 'Mandiri', 'BSI', 'BTN', 'SMBC', 'Lainnya'])->nullable();
            $table->string('nomor_rekening')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
