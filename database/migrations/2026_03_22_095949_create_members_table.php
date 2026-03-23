<?php

use App\Enums\GenderEnum;
use App\Enums\MemberStatusEnum;
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
        Schema::create('members', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('membership_number')
                ->unique()
                ->index();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', GenderEnum::values())
                ->nullable()
                ->index();
            $table->text('address')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->text('health_notes')->nullable();
            $table->date('join_date')
                ->default(now()
                )->index();
            $table->enum('status', MemberStatusEnum::values())
                ->default(MemberStatusEnum::Active->value)
                ->index();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
