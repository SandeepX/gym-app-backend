<?php

namespace App\Enums;

enum GenderEnum: int
{
    case Male = 1;
    case Female = 2;

    public function label(): string
    {
        return match ($this) {
            self::Male => 'Male',
            self::Female => 'Female',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
