<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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

        DB::table('surgeries')->insert([
            ['ma_phauthuat' => 'PT001', 'ten_phauthuat' => 'Triệt sản chó/mèo', 'gia' => 800000, 'trangthai' => true, 'created_at' => now(), 'updated_at' => now()],
            ['ma_phauthuat' => 'PT002', 'ten_phauthuat' => 'Khâu vết thương ngoài da', 'gia' => 500000, 'trangthai' => true, 'created_at' => now(), 'updated_at' => now()],
            ['ma_phauthuat' => 'PT003', 'ten_phauthuat' => 'Mổ lấy dị vật đường tiêu hóa', 'gia' => 1800000, 'trangthai' => true, 'created_at' => now(), 'updated_at' => now()],
            ['ma_phauthuat' => 'PT004', 'ten_phauthuat' => 'Mổ lấy thai', 'gia' => 2000000, 'trangthai' => true, 'created_at' => now(), 'updated_at' => now()],
            ['ma_phauthuat' => 'PT005', 'ten_phauthuat' => 'Cắt bỏ khối u nhỏ ngoài da', 'gia' => 1000000, 'trangthai' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::table('medical_records', function (Blueprint $table) {
            $table->dropColumn(['surgery_id', 'surgery_price']);
        });
        Schema::dropIfExists('surgeries');
    }
};