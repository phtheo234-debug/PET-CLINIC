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
        Schema::create('pets', function (Blueprint $table) {
            $table->id('pet_id');
            $table->string('mathucung', 20)->unique();
            $table->foreignId('owner_id')->constrained('owners', 'owner_id')->onDelete('cascade');
            $table->foreignId('cage_id')->nullable()->constrained('cages', 'cage_id')->onDelete('set null');
            $table->string('ten_thucung', 50);
            $table->string('loai', 30);
            $table->string('giong', 50)->nullable();
            $table->enum('gioitinh', ['DUC', 'CAI', 'CHUA_XAC_DINH'])->default('CHUA_XAC_DINH');
            $table->date('ngaysinh')->nullable();
            $table->string('machip', 50)->nullable()->unique();
            $table->enum('trangthai', ['NOI_TRU', 'NGOAI_TRU', 'DA_XUAT_VIEN', 'CHUYEN_VIEN'])->default('NGOAI_TRU');
            $table->timestamp('ngaytao')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pets');
    }
};