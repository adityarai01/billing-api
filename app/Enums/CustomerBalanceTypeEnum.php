<?php
namespace App\Enums;

enum CustomerBalanceTypeEnum: int
{
    case Receivable = 1;
    case Payable    = 2;

    public function label(): string
    {
        return match($this) {
            self::Receivable => 'Receivable',
            self::Payable    => 'Payable',
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
