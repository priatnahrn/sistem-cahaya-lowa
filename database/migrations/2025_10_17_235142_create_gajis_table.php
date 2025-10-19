<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gajis', function (Blueprint $table) {
            $table->id();
            $table->string('nama_karyawan');
            $table->dateTime('tanggal');
            $table->decimal('upah_harian', 15, 2)->default(0);
            $table->decimal('utang', 15, 2)->default(0);
            $table->decimal('saldo', 15, 2);
            $table->string('keterangan')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gajis');
    }
};