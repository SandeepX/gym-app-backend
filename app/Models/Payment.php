<?php

namespace App\Models;

use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use App\Traits\GenerateSequenceNumberTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

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

    public function scopeApplyFilters(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->member_id, fn ($q) => $q->where('member_id', $request->member_id))
            ->when($request->from_date, fn ($q) => $q->whereDate('paid_at', '>=', $request->from_date))
            ->when($request->to_date, fn ($q) => $q->whereDate('paid_at', '<=', $request->to_date));
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
