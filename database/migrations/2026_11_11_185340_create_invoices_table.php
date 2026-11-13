<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id('invoice_id');
            $table->string('mahoadon', 20)->unique();
            $table->foreignId('pet_id')->constrained('pets', 'pet_id');
            $table->foreignId('owner_id')->constrained('owners', 'owner_id');
            $table->decimal('tongtien', 12, 2);
            $table->enum('phuongthucthanhtoan', ['TIEN_MAT', 'CHUYEN_KHOAN', 'THE'])->default('TIEN_MAT');
            $table->enum('trangthai', ['CHO_THANH_TOAN', 'DA_THANH_TOAN', 'DA_HUY'])->default('DA_THANH_TOAN');
            $table->timestamp('ngaytao')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};