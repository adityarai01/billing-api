<?php
namespace App\Enums;

enum DiscountLevelEnum: int
{
    case ItemLevel    = 1;
    case InvoiceLevel = 2;

    public function label(): string
    {
        return match($this) {
            self::ItemLevel    => 'Item Level',
            self::InvoiceLevel => 'Invoice Level',
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
