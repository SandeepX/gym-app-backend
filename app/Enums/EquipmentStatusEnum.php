<?php

namespace App\Enums;

enum EquipmentStatusEnum: int
{
    case Active = 1;
    case Maintenance = 2;
    case Retired = 3;

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Maintenance => 'Maintenance',
            self::Retired => 'Retired',
        };
    }
}
