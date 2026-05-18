<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $table = 'suppliers';
    protected $guarded = [];
    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'balance_type' => 'integer',
        'status' => 'integer',
        'deleted' => 'integer',
    ];

    public function purchases() { return $this->hasMany(Purchase::class); }
    public function purchaseReturns() { return $this->hasMany(PurchaseReturn::class); }
    public function debitNotes() { return $this->hasMany(DebitNote::class); }
    public function supplierCreditNotes() { return $this->hasMany(SupplierCreditNote::class); }
}
