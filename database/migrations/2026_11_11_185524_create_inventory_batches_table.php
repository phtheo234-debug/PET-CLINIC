<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id('batch_id');
            $table->foreignId('item_id')->constrained('inventory', 'item_id')->onDelete('cascade');
            $table->string('solo', 50);
            $table->integer('soluong')->default(0);
            $table->date('ngaynhap');
            $table->date('ngayhethan');
            $table->enum('trangthai', ['DANG_SU_DUNG', 'HET_HAN', 'DA_TIEU_HUY'])->default('DANG_SU_DUNG');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_batches');
    }
};