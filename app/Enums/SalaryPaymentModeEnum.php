<?php

namespace App\Enums;

enum SalaryPaymentModeEnum: int
{
    case Cash         = 1;
    case BankTransfer = 2;
    case UPI          = 3;
    case Cheque       = 4;

    public function label(): string
    {
        return match($this) {
            self::Cash         => 'Cash',
            self::BankTransfer => 'Bank Transfer',
            self::UPI          => 'UPI',
            self::Cheque       => 'Cheque',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return array_map(fn($e) => ['value' => $e->value, 'label' => $e->label()], self::cases());
    }
}
