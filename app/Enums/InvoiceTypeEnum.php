<?php
namespace App\Enums;

enum InvoiceTypeEnum: int
{
    case POS        = 1;
    case TaxInvoice = 2;
    case Estimate   = 3;
    case Proforma   = 4;

    public function label(): string
    {
        return match($this) {
            self::POS        => 'POS',
            self::TaxInvoice => 'Tax Invoice',
            self::Estimate   => 'Estimate',
            self::Proforma   => 'Proforma',
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
