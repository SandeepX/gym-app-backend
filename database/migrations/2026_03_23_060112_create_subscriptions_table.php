<?php

use App\Enums\SubscriptionStatusEnum;
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
        Schema::create('subscriptions', static function (Blueprint $table) {
            $table->id();
            $table->string('subscription_number')->unique()->index();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->date('start_date')->index();
            $table->date('end_date')->index();
            $table->enum('status', SubscriptionStatusEnum::values())
                ->default(SubscriptionStatusEnum::Active->value)
                ->index();
            $table->date('freeze_start')->nullable()->index();
            $table->date('freeze_end')->nullable()->index();
            $table->integer('freeze_days_used')->default(0);
            $table->boolean('auto_renew')->default(false);
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
        Schema::dropIfExists('subscriptions');
    }
};
