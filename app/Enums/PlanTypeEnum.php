<?php

namespace App\Enums;

enum PlanTypeEnum: string
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case HalfYearly = 'half_yearly';
    case Yearly = 'yearly';
    case Custom = 'custom';

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
