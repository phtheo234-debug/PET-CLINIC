
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id('prescription_id');

            // Tạm tạo cột, khóa ngoại sẽ được thêm ở migration sau
            $table->unsignedBigInteger('record_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('invoice_id')->nullable();

            $table->unsignedInteger('soluong');
            $table->text('huongdan')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
