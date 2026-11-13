<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_records', function (Blueprint $table) {
            if (!Schema::hasColumn('medical_records', 'huong_xuly')) {
                $table->string('huong_xuly', 20)->nullable()->after('trangthai');
            }

            if (!Schema::hasColumn('medical_records', 'yeu_cau_cach_ly')) {
                $table->boolean('yeu_cau_cach_ly')->default(false);
            }
        });

        Schema::create('boarding_requests', function (Blueprint $table) {
            $table->id('request_id');
            $table->foreignId('pet_id')
                ->constrained('pets', 'pet_id')
                ->restrictOnDelete();
            $table->foreignId('medical_record_id')
                ->unique()
                ->constrained('medical_records', 'record_id')
                ->restrictOnDelete();
            $table->string('loai_long_yeu_cau', 20);
            $table->boolean('yeu_cau_cach_ly')->default(false);
            $table->string('ghi_chu', 255)->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('suggested_cage_id')
                ->nullable()
                ->constrained('cages', 'cage_id')
                ->nullOnDelete();
            $table->foreignId('confirmed_by')
                ->nullable()
                ->constrained('users', 'user_id')
                ->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['pet_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boarding_requests');

        Schema::table('medical_records', function (Blueprint $table) {
            $table->dropColumn(['huong_xuly', 'yeu_cau_cach_ly']);
        });
    }
};
