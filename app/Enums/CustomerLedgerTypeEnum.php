<?php
namespace App\Enums;

enum CustomerLedgerTypeEnum: int
{
    case OpeningBalance        = 1;
    case Sale                  = 2;
    case SalePayment           = 3;
    case SalesReturn           = 4;
    case CreditNote            = 5;
    case CreditNoteAdjustment  = 6;
    case ManualAdjustment      = 7;

    public function label(): string
    {
        return match($this) {
            self::OpeningBalance       => 'Opening Balance',
            self::Sale                 => 'Sale',
            self::SalePayment          => 'Sale Payment',
            self::SalesReturn          => 'Sales Return',
            self::CreditNote           => 'Credit Note',
            self::CreditNoteAdjustment => 'Credit Note Adjustment',
            self::ManualAdjustment     => 'Manual Adjustment',
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
