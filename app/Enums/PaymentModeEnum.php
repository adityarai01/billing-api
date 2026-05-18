<?php

namespace App\Enums;

enum PaymentModeEnum: int
{
    case Cash        = 1;
    case UPI         = 2;
    case Card        = 3;
    case BankTransfer = 4;
    case Cheque      = 5;
    case Credit      = 6;

    public function label(): string
    {
        return match($this) {
            self::Cash         => 'Cash',
            self::UPI          => 'UPI',
            self::Card         => 'Card',
            self::BankTransfer => 'Bank Transfer',
            self::Cheque       => 'Cheque',
            self::Credit       => 'Credit',
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
