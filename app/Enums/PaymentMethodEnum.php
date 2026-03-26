<?php

namespace App\Enums;

enum PaymentMethodEnum: int
{
    case Cash = 1;
    case Card = 2;
    case BankTransfer = 3;
    case Online = 4;

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Card => 'Card',
            self::BankTransfer => 'Bank Transfer',
            self::Online => 'Online',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
