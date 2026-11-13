
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_details', function (Blueprint $table) {
            $table->id('detail_id');

            // Hóa đơn chứa chi tiết này
            $table->foreignId('invoice_id')
                ->constrained('invoices', 'invoice_id')
                ->cascadeOnDelete();

            // Đơn thuốc được thanh toán
            $table->foreignId('prescription_id')
                ->unique()
                ->constrained('prescriptions', 'prescription_id')
                ->restrictOnDelete();

            // Lưu thông tin tại thời điểm thanh toán
            $table->string('ten_sanpham', 100);
            $table->string('donvitinh', 20);
            $table->unsignedInteger('soluong');

            $table->decimal('dongia', 12, 2);
            $table->decimal('thanhtien', 12, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_details');
    }
};
