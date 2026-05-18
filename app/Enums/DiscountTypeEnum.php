<?php
namespace App\Enums;

enum DiscountTypeEnum: int
{
    case Percentage = 1;
    case Fixed      = 2;
    case FreeItem   = 3;

    public function label(): string
    {
        return match($this) {
            self::Percentage => 'Percentage',
            self::Fixed      => 'Fixed',
            self::FreeItem   => 'Free Item',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn(self $case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases()
        );
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
