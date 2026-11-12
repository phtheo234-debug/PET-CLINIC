<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id('prescription_id');

            $table->foreignId('record_id')
                ->constrained('medical_records', 'record_id');

            $table->foreignId('item_id')
                ->constrained('inventory', 'item_id');

            $table->foreignId('invoice_id')
                ->nullable()
                ->constrained('invoices', 'invoice_id')
                ->nullOnDelete();

            $table->unsignedInteger('soluong');
            $table->text('huongdan')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
