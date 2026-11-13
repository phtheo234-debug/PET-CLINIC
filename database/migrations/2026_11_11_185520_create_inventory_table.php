<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory', function (Blueprint $table) {
            $table->id('item_id');
            $table->string('masanpham', 20)->unique();
            $table->string('ten_sanpham', 100);
            $table->enum('danhmuc', ['THUOC', 'VAC_XIN', 'THUC_AN', 'VAT_TU', 'DICH_VU_SPA']);
            $table->string('donvitinh', 20);
            $table->decimal('dongia', 12, 2);
            $table->integer('tonkhotoithieu')->default(10);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};