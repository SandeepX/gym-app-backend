<?php

namespace App\Enums;

enum PaymentStatusEnum: string
{
    case Paid = 'paid';
    case Pending = 'pending';
    case Refunded = 'refunded';
    case Failed = 'failed';

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
