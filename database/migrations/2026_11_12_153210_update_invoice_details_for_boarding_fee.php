
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_details', function (Blueprint $table) {
            // Khoản lưu trú không có prescription_id
            $table->unsignedBigInteger('prescription_id')
                ->nullable()
                ->change();

            // Phân biệt dòng tiền thuốc và phí lưu trú
            $table->enum('loai_chiphi', ['THUOC', 'LUU_TRU'])
                ->default('THUOC')
                ->after('prescription_id');

            $table->string('ghichu', 255)
                ->nullable()
                ->after('thanhtien');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_details', function (Blueprint $table) {
            $table->dropColumn(['loai_chiphi', 'ghichu']);

            // Chỉ đổi lại NOT NULL nếu chắc chắn không có dòng phí lưu trú
            $table->unsignedBigInteger('prescription_id')
                ->nullable(false)
                ->change();
        });
    }
};
