<?php

namespace App\Enums;

enum OrganizationType: int
{
    case Medical = 1;
    case Cloth   = 2;
    case Grocery = 3;
    case General = 4;

    public function label(): string
    {
        return match($this) {
            self::Medical => 'Medical / Pharmacy',
            self::Cloth   => 'Clothing / Textile',
            self::Grocery => 'Grocery',
            self::General => 'General Store',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
