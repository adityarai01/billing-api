<?php

namespace App\Enums;

enum SubscriptionStatus: int
{
    case Trial     = 1;
    case Active    = 2;
    case Expired   = 3;
    case Suspended = 4;

    public function label(): string
    {
        return match($this) {
            self::Trial     => 'Trial',
            self::Active    => 'Active',
            self::Expired   => 'Expired',
            self::Suspended => 'Suspended',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
