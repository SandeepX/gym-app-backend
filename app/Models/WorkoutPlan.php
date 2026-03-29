<?php

namespace App\Models;

use App\Enums\WorkOutDifficultyLevelEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkoutPlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'difficulty',
        'duration_weeks',
        'days_per_week',
        'goals',
        'is_active',
        'created_by',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(WorkoutExercise::class)->orderBy('day_number')->orderBy('order');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'member_workout_plans')
            ->withPivot(['assigned_by', 'start_date', 'end_date', 'status', 'notes'])
            ->withTimestamps();
    }

    protected function casts(): array
    {
        return [
            'goals' => 'array',
            'is_active' => 'boolean',
            'difficulty' => WorkOutDifficultyLevelEnum::class,
        ];
    }

    public function scopeFilter($query, array $filters)
    {
        return $query
            ->where('is_active', true)
            ->when($filters['difficulty'] ?? null, fn ($q) => $q->where('difficulty', $filters['difficulty']))
            ->when($filters['search'] ?? null, fn ($q) => $q->where('name', 'like', "%{$filters['search']}%"));
    }
}
