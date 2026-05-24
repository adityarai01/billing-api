<?php
namespace App\Enums;

enum CashRegisterSourceTypeEnum: int
{
    case Sale          = 1;
    case SalesReturn   = 2;
    case Expense       = 3;
    case ManualCashIn  = 4;
    case ManualCashOut = 5;
    case Opening       = 6;
    case Closing       = 7;

    public function label(): string
    {
        return match($this) {
            self::Sale          => 'Sale',
            self::SalesReturn   => 'Sales Return',
            self::Expense       => 'Expense',
            self::ManualCashIn  => 'Manual Cash In',
            self::ManualCashOut => 'Manual Cash Out',
            self::Opening       => 'Opening',
            self::Closing       => 'Closing',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return array_map(fn($c) => ['value' => $c->value, 'label' => $c->label()], self::cases());
    }
}
