<?php

namespace App\Enums;

enum SubscriptionStatusEnum: string
{
    case Active = 'active';
    case Expired = 'expired';
    case Frozen = 'frozen';
    case Cancelled = 'cancelled';

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
