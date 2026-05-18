<?php

namespace App\Enums;

enum InvoicePaperSizeEnum: int
{
    case Thermal80mm = 1;
    case Thermal58mm = 2;
    case A4 = 3;

    public function label(): string
    {
        return match ($this) {
            self::Thermal80mm => 'Thermal 80mm',
            self::Thermal58mm => 'Thermal 58mm',
            self::A4 => 'A4',
        };
    }

    public function slug(): string
    {
        return match ($this) {
            self::Thermal80mm => '80mm',
            self::Thermal58mm => '58mm',
            self::A4 => 'a4',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn(self $case) => [
                'value' => $case->value,
                'label' => $case->label(),
                'slug' => $case->slug(),
            ],
            self::cases()
        );
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
