<?php

namespace App\Models;

use App\Enums\GenderEnum;
use App\Enums\MemberStatusEnum;
use App\Enums\SubscriptionStatusEnum;
use App\Traits\GenerateSequenceNumberTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use GenerateSequenceNumberTrait, SoftDeletes;

    protected $fillable = [
        'user_id',
        'membership_number',
        'date_of_birth',
        'gender',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'health_notes',
        'join_date',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where('status', SubscriptionStatusEnum::Active)
            ->latest();
    }

    public function latestSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class)->latest('check_in');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function trainers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'member_trainer', 'member_id', 'user_id')
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('status', MemberStatusEnum::Active);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', MemberStatusEnum::Inactive->value);
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', MemberStatusEnum::Suspended->value);
    }

    public function measurements(): HasMany
    {
        return $this->hasMany(BodyMeasurement::class)->latest('measured_at');
    }

    public function latestMeasurement(): HasOne
    {
        return $this->hasOne(BodyMeasurement::class)->latestOfMany('measured_at');
    }

    public function workoutPlans(): BelongsToMany
    {
        return $this->belongsToMany(WorkoutPlan::class, 'member_workout_plans')
            ->withPivot(['assigned_by', 'start_date', 'end_date', 'status', 'notes'])
            ->withTimestamps();
    }

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'join_date' => 'date',
            'status' => MemberStatusEnum::class,
            'gender' => GenderEnum::class,
        ];
    }
}
