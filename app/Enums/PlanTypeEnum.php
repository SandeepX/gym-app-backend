<?php

namespace App\Enums;

enum PlanTypeEnum: int
{
    case Monthly = 1;
    case Quarterly = 2;
    case HalfYearly = 3;
    case Yearly = 4;
    case Custom = 5;

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly',
            self::Quarterly => 'Quarterly',
            self::HalfYearly => 'Half Yearly',
            self::Yearly => 'Yearly',
            self::Custom => 'Custom',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
