<?php

namespace App\Enums;

enum SubscriptionStatusEnum: int
{
    case Active = 1;
    case Expired = 2;
    case Frozen = 3;
    case Cancelled = 4;

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Expired => 'Expired',
            self::Frozen => 'Frozen',
            self::Cancelled => 'Cancelled',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
