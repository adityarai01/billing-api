<?php
namespace App\Events;
use App\Models\DebitNote;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
class DebitNoteAdjusted
{
    use Dispatchable, SerializesModels;
    public function __construct(public DebitNote $debitNote) {}
}
