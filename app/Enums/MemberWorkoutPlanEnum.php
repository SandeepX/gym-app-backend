<?php

namespace App\Enums;

enum MemberWorkoutPlanEnum: int
{
    case ACTIVE = 1;
    case COMPLETED = 2;
    case CANCELLED = 3;

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }
}
