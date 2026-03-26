<?php

use App\Enums\EquipmentStatusEnum;
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
        Schema::create('equipment', static function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('serial_number')->unique()->nullable()->index();
            $table->string('category')->index();
            $table->string('brand')->nullable();
            $table->text('description')->nullable();
            $table->integer('status')
                ->default(EquipmentStatusEnum::Active->value)
                ->index();
            $table->date('purchase_date')->nullable()->index();
            $table->decimal('purchase_price', 10, 2)->nullable()->index();
            $table->date('last_maintenance_date')->nullable();
            $table->date('next_maintenance_date')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
