<?php

namespace App\Enums;

enum PaymentStatusEnum: int
{
    case Paid = 1;
    case Pending = 2;
    case Refunded = 3;
    case Failed = 4;

    public function label(): string
    {
        return match ($this) {
            self::Paid => 'Paid',
            self::Pending => 'Pending',
            self::Refunded => 'Refunded',
            self::Failed => 'Failed',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
