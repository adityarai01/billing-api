<?php
namespace App\Events;
use App\Models\SupplierCreditNote;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
class SupplierCreditNoteAdjusted
{
    use Dispatchable, SerializesModels;
    public function __construct(public SupplierCreditNote $creditNote) {}
}
