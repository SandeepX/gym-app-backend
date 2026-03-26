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
        Schema::create('body_measurements', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('weight', 5, 2)->nullable()->comment('kg');
            $table->decimal('height', 5, 2)->nullable()->comment('cm');
            $table->decimal('bmi', 4, 2)->nullable();
            $table->decimal('body_fat_percentage', 4, 2)->nullable();
            $table->decimal('muscle_mass', 5, 2)->nullable()->comment('kg');
            $table->decimal('chest', 5, 2)->nullable()->comment('cm');
            $table->decimal('waist', 5, 2)->nullable()->comment('cm');
            $table->decimal('hips', 5, 2)->nullable()->comment('cm');
            $table->decimal('biceps', 5, 2)->nullable()->comment('cm');
            $table->decimal('thighs', 5, 2)->nullable()->comment('cm');
            $table->text('notes')->nullable();
            $table->date('measured_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('body_measurements');
    }
};
