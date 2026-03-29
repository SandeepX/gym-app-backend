<?php

namespace App\Models;

use App\Enums\PlanTypeEnum;
use App\Traits\GenerateSequenceNumberTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use GenerateSequenceNumberTrait, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'price',
        'duration_days',
        'max_freeze_days',
        'type',
        'features',
        'is_active',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'duration_days' => 'integer',
            'max_freeze_days' => 'integer',
            'features' => 'array',
            'is_active' => 'boolean',
            'type' => PlanTypeEnum::class,
        ];
    }

    public function scopeFilter($query, array $filters)
    {
        return $query
            ->when(
                $filters['type'] ?? null,
                fn ($q) => $q->where('type', $filters['type'])
            )
            ->when(
                isset($filters['is_active']),
                fn ($q) => $q->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN))
            )
            ->when(
                $filters['search'] ?? null,
                fn ($q) => $q->where('name', 'like', "%{$filters['search']}%")
            );
    }
}
