<?php
namespace App\Enums;

enum CashRegisterStatusEnum: int
{
    case Open      = 1;
    case Closed    = 2;
    case Cancelled = 3;

    public function label(): string
    {
        return match($this) {
            self::Open      => 'Open',
            self::Closed    => 'Closed',
            self::Cancelled => 'Cancelled',
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
