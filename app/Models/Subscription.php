<?php

namespace App\Models;

use App\Enums\SubscriptionStatusEnum;
use App\Traits\GenerateSequenceNumberTrait;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;

class Subscription extends Model
{
    use GenerateSequenceNumberTrait, SoftDeletes;

    protected $fillable = [
        'subscription_number',
        'member_id',
        'plan_id',
        'start_date',
        'end_date',
        'status',
        'freeze_start',
        'freeze_end',
        'freeze_days_used',
        'auto_renew',
        'notes',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function daysRemaining(): int
    {
        if ($this->end_date < Carbon::today()) {
            return 0;
        }

        return Carbon::today()->diffInDays($this->end_date);
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'freeze_start' => 'date',
            'freeze_end' => 'date',
            'freeze_days_used' => 'integer',
            'auto_renew' => 'boolean',
            'status' => SubscriptionStatusEnum::class,
        ];
    }

    public function scopeApplyFilters(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->member_id, fn ($q) => $q->where('member_id', $request->member_id))
            ->when($request->plan_id, fn ($q) => $q->whereDate('plan_id', '>=', $request->plan_id));
    }
}
