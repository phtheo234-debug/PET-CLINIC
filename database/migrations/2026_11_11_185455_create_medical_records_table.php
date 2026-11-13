<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id('record_id');
            $table->foreignId('pet_id')->constrained('pets', 'pet_id');
            $table->foreignId('doctor_id')->constrained('users', 'user_id');
            $table->timestamp('ngaykham')->useCurrent();
            $table->text('trieuchung')->nullable();
            $table->text('chandoan');
            $table->decimal('cannangkhi_kham', 5, 2);
            $table->enum('trangthai', ['DANG_DIEU_TRI', 'HOAN_THANH', 'CHUYEN_VIEN'])->default('HOAN_THANH');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_records');
    }
};