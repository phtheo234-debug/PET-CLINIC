<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Support\\Facades\\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE inventory_batches MODIFY ngayhethan DATE NULL");
    }

    public function down(): void
    {
        // Các lô không có hạn sử dụng cần được gán ngày trước khi rollback.
        DB::statement("UPDATE inventory_batches SET ngayhethan = '2099-12-31' WHERE ngayhethan IS NULL");
        DB::statement("ALTER TABLE inventory_batches MODIFY ngayhethan DATE NOT NULL");
    }
};
