<?php
namespace App\Events;
use App\Models\StockAdjustment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
class StockAdjusted
{
    use Dispatchable, SerializesModels;
    public function __construct(public StockAdjustment $adjustment) {}
}
