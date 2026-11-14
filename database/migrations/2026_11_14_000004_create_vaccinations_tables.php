<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vaccination_types', function (Blueprint $table) {
            $table->id('vaccination_type_id');
            $table->string('name', 100)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('pet_vaccinations', function (Blueprint $table) {
            $table->id('vaccination_id');
            $table->foreignId('pet_id')->constrained('pets', 'pet_id')->cascadeOnDelete();
            $table->foreignId('vaccination_type_id')->constrained('vaccination_types', 'vaccination_type_id');
            $table->date('vaccinated_at');
            $table->date('next_due_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('administered_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->timestamps();
            $table->index(['pet_id', 'vaccinated_at']);
            $table->index('next_due_date');
        });

        foreach (['Vắc-xin 1', 'Vắc-xin 2', 'Vắc-xin 3', 'Vắc-xin 4', 'Vắc-xin 5', 'Vắc-xin 6'] as $name) {
            DB::table('vaccination_types')->insert([
                'name' => $name,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pet_vaccinations');
        Schema::dropIfExists('vaccination_types');
    }
};
