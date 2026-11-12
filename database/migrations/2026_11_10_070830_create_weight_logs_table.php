<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weight_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pet_id')->constrained('pets', 'pet_id')->onDelete('cascade');
            $table->decimal('cannang', 5, 2);
            $table->timestamp('ngaydo')->useCurrent();
            $table->foreignId('nguoido')->constrained('users', 'user_id');
            $table->string('ghichu', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weight_logs');
    }
};