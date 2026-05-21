<?php
namespace App\Enums;

enum PromotionStatusEnum: int
{
    case Inactive = 0;
    case Active   = 1;

    public function label(): string
    {
        return match($this) {
            self::Inactive => 'Inactive',
            self::Active   => 'Active',
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
