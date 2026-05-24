<?php
namespace App\Enums;

enum CashRegisterTransactionTypeEnum: int
{
    case OpeningCash       = 1;
    case CashSale          = 2;
    case CashRefund        = 3;
    case CashIn            = 4;
    case CashOut           = 5;
    case Expense           = 6;
    case ClosingAdjustment = 7;
    case UPISale           = 8;
    case CardSale          = 9;
    case BankTransferSale  = 10;
    case CreditSale        = 11;

    public function label(): string
    {
        return match($this) {
            self::OpeningCash       => 'Opening Cash',
            self::CashSale          => 'Cash Sale',
            self::CashRefund        => 'Cash Refund',
            self::CashIn            => 'Cash In',
            self::CashOut           => 'Cash Out',
            self::Expense           => 'Expense',
            self::ClosingAdjustment => 'Closing Adjustment',
            self::UPISale           => 'UPI Sale',
            self::CardSale          => 'Card Sale',
            self::BankTransferSale  => 'Bank Transfer Sale',
            self::CreditSale        => 'Credit Sale',
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
