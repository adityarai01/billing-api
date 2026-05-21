<?php
namespace App\Enums;

enum BuyGetTargetTypeEnum: int
{
    case SameProduct    = 1;
    case Product        = 2;
    case ProductVariant = 3;
    case Category       = 4;
    case Brand          = 5;

    public function label(): string
    {
        return match($this) {
            self::SameProduct    => 'Same Product',
            self::Product        => 'Product',
            self::ProductVariant => 'Product Variant',
            self::Category       => 'Category',
            self::Brand          => 'Brand',
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
