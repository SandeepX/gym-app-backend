<?php

namespace App\Models;

use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use App\Traits\GenerateSequenceNumberTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use GenerateSequenceNumberTrait;

    protected $fillable = [
        'invoice_number',
        'member_id',
        'subscription_id',
        'collected_by',
        'amount',
        'payment_method',
        'status',
        'reference_number',
        'paid_at',
        'notes',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'payment_method' => PaymentMethodEnum::class,
            'status' => PaymentStatusEnum::class,
        ];
    }
}
