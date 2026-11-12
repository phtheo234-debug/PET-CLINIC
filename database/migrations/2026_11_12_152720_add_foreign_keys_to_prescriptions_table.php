
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function hasForeignKey(string $name): bool
    {
        return DB::selectOne(
            'SELECT CONSTRAINT_NAME
             FROM information_schema.REFERENTIAL_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
             AND TABLE_NAME = ?
             AND CONSTRAINT_NAME = ?',
            ['prescriptions', $name]
        ) !== null;
    }

    public function up(): void
    {
        // Khóa ngoại tới medical_records
        if (!$this->hasForeignKey('prescriptions_record_id_foreign')) {
            Schema::table('prescriptions', function (Blueprint $table) {
                $table->foreign('record_id', 'prescriptions_record_id_foreign')
                    ->references('record_id')
                    ->on('medical_records');
            });
        }

        // Khóa ngoại tới inventory
        if (!$this->hasForeignKey('prescriptions_item_id_foreign')) {
            Schema::table('prescriptions', function (Blueprint $table) {
                $table->foreign('item_id', 'prescriptions_item_id_foreign')
                    ->references('item_id')
                    ->on('inventory');
            });
        }

        // Khóa ngoại tới invoices
        if (!$this->hasForeignKey('prescriptions_invoice_id_foreign')) {
            Schema::table('prescriptions', function (Blueprint $table) {
                $table->foreign('invoice_id', 'prescriptions_invoice_id_foreign')
                    ->references('invoice_id')
                    ->on('invoices')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            foreach ([
                'prescriptions_record_id_foreign',
                'prescriptions_item_id_foreign',
                'prescriptions_invoice_id_foreign',
            ] as $key) {
                if ($this->hasForeignKey($key)) {
                    $table->dropForeign($key);
                }
            }
        });
    }
};
