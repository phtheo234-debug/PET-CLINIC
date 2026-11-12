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
        Schema::create('cages', function (Blueprint $table) {
            $table->id('cage_id');
            $table->string('malong', 20)->unique();
            $table->string('ten_long', 50);
            $table->enum('loailong', ['THUONG', 'CAP_CUU_ICU', 'CACH_LY'])->default('THUONG');
            $table->integer('succhua')->default(1);
            $table->integer('soluonghientai')->default(0);
            $table->enum('trangthai', ['CON_TRONG', 'DA_CO_THU', 'DANG_VE_SINH', 'BAO_TRI'])->default('CON_TRONG');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cages');
    }
};