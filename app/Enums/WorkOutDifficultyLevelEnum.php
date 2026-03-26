<?php

namespace App\Enums;

enum WorkOutDifficultyLevelEnum: int
{
    case Beginner = 1;
    case Intermediate = 2;
    case Advanced = 3;

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Beginner => 'Beginner',
            self::Intermediate => 'Intermediate',
            self::Advanced => 'Advanced',
        };
    }
}
