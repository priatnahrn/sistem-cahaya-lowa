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
        Schema::create('pembelians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('no_faktur')->unique();
            $table->dateTime('tanggal')->index();
            $table->text('deskripsi')->nullable();
            $table->decimal('sub_total', 18, 2)->default(0);
            $table->decimal('biaya_transport', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);
            $table->enum('status', ['paid', 'unpaid', 'return'])->default('unpaid');
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
        Schema::dropIfExists('pembelians');
        
    }
};
