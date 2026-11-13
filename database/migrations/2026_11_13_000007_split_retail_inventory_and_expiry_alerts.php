<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Support\\Facades\\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Bỏ Vật tư, bổ sung Đồ dùng để quản lý kho bán lẻ riêng với thuốc/vắc-xin.
        DB::statement("ALTER TABLE inventory MODIFY danhmuc ENUM('THUOC','VAC_XIN','THUC_AN','DO_DUNG','DICH_VU_SPA') NOT NULL");
        // Các loại chi phí bán lẻ được lưu trực tiếp trên hóa đơn, không cần prescription_id.
        DB::statement("ALTER TABLE invoice_details MODIFY loai_chiphi ENUM('THUOC','VAC_XIN','LUU_TRU','DICH_VU_SPA','PHI_KHAM','THUC_AN','DO_DUNG') NOT NULL DEFAULT 'THUOC'");
    }

    public function down(): void
    {
        DB::statement("UPDATE inventory SET danhmuc = 'THUC_AN' WHERE danhmuc = 'DO_DUNG'");
        DB::statement("ALTER TABLE inventory MODIFY danhmuc ENUM('THUOC','VAC_XIN','THUC_AN','VAT_TU','DICH_VU_SPA') NOT NULL");
        DB::statement("UPDATE invoice_details SET loai_chiphi = 'DICH_VU_SPA' WHERE loai_chiphi IN ('THUC_AN','DO_DUNG')");
        DB::statement("ALTER TABLE invoice_details MODIFY loai_chiphi ENUM('THUOC','VAC_XIN','LUU_TRU','DICH_VU_SPA','PHI_KHAM') NOT NULL DEFAULT 'THUOC'");
    }
};
