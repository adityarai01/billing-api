<?php
namespace App\Enums;

enum HeldBillStatusEnum: int
{
    case Held             = 1;
    case ConvertedToSale  = 2;
    case Cancelled        = 3;

    public function label(): string
    {
        return match($this) {
            self::Held            => 'Held',
            self::ConvertedToSale => 'Converted To Sale',
            self::Cancelled       => 'Cancelled',
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
