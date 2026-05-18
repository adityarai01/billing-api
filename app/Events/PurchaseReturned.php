<?php
namespace App\Events;
use App\Models\PurchaseReturn;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
class PurchaseReturned
{
    use Dispatchable, SerializesModels;
    public function __construct(public PurchaseReturn $purchaseReturn) {}
}
