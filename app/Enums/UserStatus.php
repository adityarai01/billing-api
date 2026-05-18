<?php

namespace App\Enums;

enum UserStatus: int
{
    case Active   = 1;
    case Inactive = 0;

    public function label(): string
    {
        return match($this) {
            self::Active   => 'Active',
            self::Inactive => 'Inactive',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
