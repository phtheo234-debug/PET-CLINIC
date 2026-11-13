<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pet_boardings', function (Blueprint $table) {
            $table->foreignId('invoice_id')
                ->nullable()
                ->after('status')
                ->constrained('invoices', 'invoice_id')
                ->nullOnDelete();

            $table->index(['pet_id', 'invoice_id']);
        });

        // Đối chiếu các lượt lưu trú cũ với chi tiết hóa đơn đã phát hành,
        // để không thu lại những lượt đã được thanh toán trước khi có invoice_id.
        $oldStays = DB::table('pet_boardings as pb')
            ->join('cages as c', 'c.cage_id', '=', 'pb.cage_id')
            ->whereNotNull('pb.check_out')
            ->whereNull('pb.invoice_id')
            ->get([
                'pb.id',
                'pb.pet_id',
                'pb.check_in',
                'pb.check_out',
                'c.malong',
            ]);

        foreach ($oldStays as $stay) {
            if (!$stay->malong) {
                continue;
            }

            $checkInText = Carbon::parse($stay->check_in)->format('d/m/Y H:i');
            $checkOutText = Carbon::parse($stay->check_out)->format('d/m/Y H:i');
            $description = 'Phí lưu trú lồng ' . $stay->malong;
            $note = 'Từ ' . $checkInText . ' đến ' . $checkOutText;

            $invoiceId = DB::table('invoice_details as d')
                ->join('invoices as i', 'i.invoice_id', '=', 'd.invoice_id')
                ->where('i.pet_id', $stay->pet_id)
                ->where('d.loai_chiphi', 'LUU_TRU')
                ->where('d.ten_sanpham', $description)
                ->where('d.ghichu', $note)
                ->orderBy('i.invoice_id')
                ->value('i.invoice_id');

            // Tương thích hóa đơn cũ: ghi chú trước đây có dạng "Từ ngày ... đến ngày ...".
            if (!$invoiceId) {
                $legacyNote = 'Từ ngày ' . $checkInText . ' đến ngày ' . $checkOutText;
                $invoiceId = DB::table('invoice_details as d')
                    ->join('invoices as i', 'i.invoice_id', '=', 'd.invoice_id')
                    ->where('i.pet_id', $stay->pet_id)
                    ->where('d.loai_chiphi', 'LUU_TRU')
                    ->where('d.ten_sanpham', $description)
                    ->where('d.ghichu', $legacyNote)
                    ->orderBy('i.invoice_id')
                    ->value('i.invoice_id');
            }

            if ($invoiceId) {
                DB::table('pet_boardings')
                    ->where('id', $stay->id)
                    ->update(['invoice_id' => $invoiceId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('pet_boardings', function (Blueprint $table) {
            $table->dropIndex(['pet_id', 'invoice_id']);
            $table->dropConstrainedForeignId('invoice_id');
        });
    }
};
