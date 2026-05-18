<?php

namespace App\Enums;

enum PurchaseReturnSettlementTypeEnum: int
{
    case DebitNote        = 1;
    case CashReceived     = 2;
    case AdjustSupplierDue = 3;

    public function label(): string
    {
        return match($this) {
            self::DebitNote         => 'Debit Note',
            self::CashReceived      => 'Cash Received',
            self::AdjustSupplierDue => 'Adjust Supplier Due',
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
