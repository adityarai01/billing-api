<?php
namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PromotionApplied
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int   $saleId,
        public int   $organizationId,
        public array $appliedPromotions,
        public ?int  $customerId = null,
    ) {}
}
