<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surgeries', function (Blueprint $table) {
            $table->id('surgery_id');
            $table->string('ma_phauthuat', 30)->unique();
            $table->string('ten_phauthuat', 100);
            $table->unsignedBigInteger('gia');
            $table->boolean('trangthai')->default(true);
            $table->timestamps();
        });

        Schema::table('medical_records', function (Blueprint $table) {
            $table->unsignedBigInteger('surgery_id')->nullable()->after('yeu_cau_cach_ly');
            $table->unsignedBigInteger('surgery_price')->nullable()->after('surgery_id');
        });
    }

    public function down(): void
    {
        Schema::table('medical_records', function (Blueprint $table) {
            $table->dropColumn(['surgery_id', 'surgery_price']);
        });
        Schema::dropIfExists('surgeries');
    }
};