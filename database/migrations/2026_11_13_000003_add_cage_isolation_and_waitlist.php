<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cages', function (Blueprint $table) {
            if (!Schema::hasColumn('cages', 'loai_phu_hop')) {
                $table->string('loai_phu_hop', 30)->default('TAT_CA');
            }
            if (!Schema::hasColumn('cages', 'tai_trong_toi_da')) {
                $table->decimal('tai_trong_toi_da', 7, 2)->nullable();
            }
        });

        Schema::table('pet_boardings', function (Blueprint $table) {
            if (!Schema::hasColumn('pet_boardings', 'isolation_required')) {
                $table->boolean('isolation_required')->default(false);
            }
        });

        Schema::table('medical_records', function (Blueprint $table) {
            if (!Schema::hasColumn('medical_records', 'huong_xuly')) {
                $table->string('huong_xuly', 20)->nullable();
            }
            if (!Schema::hasColumn('medical_records', 'isolation_required')) {
                $table->boolean('isolation_required')->default(false);
            }
        });

        if (!Schema::hasTable('pet_cage_waitlist')) {
            Schema::create('pet_cage_waitlist', function (Blueprint $table) {
                $table->id('waitlist_id');
                $table->foreignId('pet_id')->constrained('pets', 'pet_id')->cascadeOnDelete();
                $table->decimal('weight', 7, 2);
                $table->string('treatment_type', 20);
                $table->boolean('isolation_required')->default(false);
                $table->string('status', 20)->default('WAITING');
                $table->foreignId('assigned_cage_id')->nullable()
                    ->constrained('cages', 'cage_id')->nullOnDelete();
                $table->timestamp('assigned_at')->nullable();
                $table->string('note', 255)->nullable();
                $table->timestamps();
                $table->index(['status', 'created_at']);
                $table->index(['pet_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pet_cage_waitlist');

        if (Schema::hasColumn('medical_records', 'isolation_required')) {
            Schema::table('medical_records', fn (Blueprint $table) => $table->dropColumn('isolation_required'));
        }
        if (Schema::hasColumn('medical_records', 'huong_xuly')) {
            Schema::table('medical_records', fn (Blueprint $table) => $table->dropColumn('huong_xuly'));
        }
        if (Schema::hasColumn('pet_boardings', 'isolation_required')) {
            Schema::table('pet_boardings', fn (Blueprint $table) => $table->dropColumn('isolation_required'));
        }
        if (Schema::hasColumn('cages', 'tai_trong_toi_da')) {
            Schema::table('cages', fn (Blueprint $table) => $table->dropColumn('tai_trong_toi_da'));
        }
        if (Schema::hasColumn('cages', 'loai_phu_hop')) {
            Schema::table('cages', fn (Blueprint $table) => $table->dropColumn('loai_phu_hop'));
        }
    }
};
