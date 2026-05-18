<?php

namespace App\Enums;

enum Gender: int
{
    case Male   = 1;
    case Female = 2;
    case Other  = 3;

    public function label(): string
    {
        return match($this) {
            self::Male   => 'Male',
            self::Female => 'Female',
            self::Other  => 'Other',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
