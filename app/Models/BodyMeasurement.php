<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BodyMeasurement extends Model
{
    protected $fillable = [
        'member_id',
        'recorded_by',
        'weight',
        'height',
        'bmi',
        'body_fat_percentage',
        'muscle_mass',
        'chest',
        'waist',
        'hips',
        'biceps',
        'thighs',
        'notes',
        'measured_at',
    ];

    public static function calculateBmi(float $weight, float $height): float
    {
        if ($height <= 0) {
            return 0;
        }
        $heightInMeters = $height / 100;

        return round($weight / ($heightInMeters * $heightInMeters), 2);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function bmiCategory(): string
    {
        return match (true) {
            $this->bmi < 18.5 => 'Underweight',
            $this->bmi < 25.0 => 'Normal',
            $this->bmi < 30.0 => 'Overweight',
            default => 'Obese',
        };
    }

    protected function casts(): array
    {
        return [
            'measured_at' => 'date',
            'weight' => 'decimal:2',
            'height' => 'decimal:2',
            'bmi' => 'decimal:2',
            'body_fat_percentage' => 'decimal:2',
            'muscle_mass' => 'decimal:2',
            'chest' => 'decimal:2',
            'waist' => 'decimal:2',
            'hips' => 'decimal:2',
            'biceps' => 'decimal:2',
            'thighs' => 'decimal:2',
        ];
    }
}
