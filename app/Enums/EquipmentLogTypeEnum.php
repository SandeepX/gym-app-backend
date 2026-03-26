<?php

namespace App\Enums;

enum EquipmentLogTypeEnum: int
{
    case Routine = 1;
    case Repair = 2;
    case Inspection = 3;

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Routine => 'Routine',
            self::Repair => 'Repair',
            self::Inspection => 'Inspection',
        };
    }
}
