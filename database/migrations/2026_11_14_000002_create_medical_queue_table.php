<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_queue', function (Blueprint $table) {
            $table->id('queue_id');
            $table->foreignId('pet_id')->constrained('pets', 'pet_id');
            $table->foreignId('created_by')->constrained('users', 'user_id');
            $table->text('ghi_chu')->nullable();
            $table->enum('trangthai', ['CHO_KHAM', 'DANG_KHAM', 'DA_KHAM'])->default('CHO_KHAM');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->index(['trangthai', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_queue');
    }
};
