<?php
namespace App\Events;
use App\Models\HeldBill;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HeldBillConverted
{
    use Dispatchable, SerializesModels;
    public function __construct(public HeldBill $heldBill) {}
}
