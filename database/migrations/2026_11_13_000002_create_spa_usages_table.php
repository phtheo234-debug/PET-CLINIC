<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spa_usages', function (Blueprint $table) {
            $table->id('spa_usage_id');
            $table->foreignId('pet_id')->constrained('pets', 'pet_id')->restrictOnDelete();
            $table->foreignId('item_id')->constrained('inventory', 'item_id')->restrictOnDelete();
            $table->foreignId('staff_id')->constrained('users', 'user_id')->restrictOnDelete();
            $table->unsignedInteger('soluong');
            $table->string('ten_dichvu', 100);
            $table->string('donvitinh', 20);
            $table->decimal('dongia', 12, 2);
            $table->decimal('thanhtien', 12, 2);
            $table->string('ghichu', 255)->nullable();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices', 'invoice_id')->nullOnDelete();
            $table->timestamps();

            $table->index(['pet_id', 'invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spa_usages');
    }
};
