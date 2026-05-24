<?php
namespace App\Services;

use App\Models\CashRegister;
use App\Models\CashRegisterTransaction;
use App\Enums\CashRegisterStatusEnum;
use App\Enums\CashRegisterTransactionTypeEnum;
use App\Enums\CashRegisterSourceTypeEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\NotificationPriorityEnum;
use App\Enums\NotificationSourceTypeEnum;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CashRegisterService
{
    public function __construct(private NotificationService $notificationService) {}

    private function cacheKey(int $orgId, int $userId, string $type): string
    {
        return "org:{$orgId}:cash-register:{$type}:user:{$userId}";
    }

    public function openRegister(int $organizationId, int $userId, array $data): CashRegister
    {
        $open = $this->getOpenRegister($organizationId, $userId);
        if ($open) {
            throw new \Exception('You already have an open register. Please close it first.');
        }

        return DB::transaction(function () use ($organizationId, $userId, $data) {
            $registerNo = $this->generateRegisterNo($organizationId);
            $openingCash = (float) ($data['opening_cash'] ?? 0);

            $register = CashRegister::create([
                'organization_id' => $organizationId,
                'user_id'         => $userId,
                'register_no'     => $registerNo,
                'opening_cash'    => $openingCash,
                'expected_cash'   => $openingCash,
                'register_status' => CashRegisterStatusEnum::Open->value,
                'opened_at'       => now(),
                'opening_note'    => $data['opening_note'] ?? null,
                'created_by'      => $userId,
            ]);

            CashRegisterTransaction::create([
                'organization_id'  => $organizationId,
                'cash_register_id' => $register->id,
                'user_id'          => $userId,
                'transaction_type' => CashRegisterTransactionTypeEnum::OpeningCash->value,
                'source_type'      => CashRegisterSourceTypeEnum::Opening->value,
                'amount'           => $openingCash,
                'reason'           => 'Opening cash',
                'created_by'       => $userId,
            ]);

            Cache::forget($this->cacheKey($organizationId, $userId, 'open'));
            return $register;
        });
    }

    public function closeRegister(int $organizationId, int $userId, array $data): CashRegister
    {
        $register = $this->getOpenRegister($organizationId, $userId);
        if (!$register) {
            throw new \Exception('No open register found.');
        }

        return DB::transaction(function () use ($organizationId, $userId, $register, $data) {
            $actualCash   = (float) ($data['actual_cash'] ?? 0);
            $expectedCash = (float) $register->expected_cash;
            $difference   = $actualCash - $expectedCash;

            $register->update([
                'actual_cash'     => $actualCash,
                'expected_cash'   => $expectedCash,
                'difference_amount' => $difference,
                'register_status' => CashRegisterStatusEnum::Closed->value,
                'closed_at'       => now(),
                'closing_note'    => $data['closing_note'] ?? null,
                'closed_by'       => $userId,
            ]);

            if ($difference != 0) {
                $this->createMismatchNotification($organizationId, $register, $difference, $userId);
            }

            Cache::forget($this->cacheKey($organizationId, $userId, 'open'));
            Cache::forget("org:{$organizationId}:cash-register:{$register->id}:summary");
            return $register->fresh();
        });
    }

    public function cashIn(int $organizationId, int $userId, array $data): CashRegisterTransaction
    {
        $register = $this->getOpenRegister($organizationId, $userId);
        if (!$register) throw new \Exception('No open register found.');

        return DB::transaction(function () use ($organizationId, $userId, $register, $data) {
            $amount = (float) $data['amount'];
            $txn = CashRegisterTransaction::create([
                'organization_id'  => $organizationId,
                'cash_register_id' => $register->id,
                'user_id'          => $userId,
                'transaction_type' => CashRegisterTransactionTypeEnum::CashIn->value,
                'source_type'      => CashRegisterSourceTypeEnum::ManualCashIn->value,
                'amount'           => $amount,
                'reason'           => $data['reason'],
                'note'             => $data['note'] ?? null,
                'created_by'       => $userId,
            ]);
            $register->increment('cash_in', $amount);
            $register->increment('expected_cash', $amount);
            Cache::forget("org:{$organizationId}:cash-register:{$register->id}:summary");
            return $txn;
        });
    }

    public function cashOut(int $organizationId, int $userId, array $data): CashRegisterTransaction
    {
        $register = $this->getOpenRegister($organizationId, $userId);
        if (!$register) throw new \Exception('No open register found.');

        return DB::transaction(function () use ($organizationId, $userId, $register, $data) {
            $amount = (float) $data['amount'];
            $txn = CashRegisterTransaction::create([
                'organization_id'  => $organizationId,
                'cash_register_id' => $register->id,
                'user_id'          => $userId,
                'transaction_type' => CashRegisterTransactionTypeEnum::CashOut->value,
                'source_type'      => CashRegisterSourceTypeEnum::ManualCashOut->value,
                'amount'           => $amount,
                'reason'           => $data['reason'],
                'note'             => $data['note'] ?? null,
                'created_by'       => $userId,
            ]);
            $register->increment('cash_out', $amount);
            $register->decrement('expected_cash', $amount);
            Cache::forget("org:{$organizationId}:cash-register:{$register->id}:summary");
            return $txn;
        });
    }

    public function recordSaleTransaction(int $organizationId, int $userId, int $saleId, array $payments): void
    {
        $register = $this->getOpenRegister($organizationId, $userId);
        if (!$register) return;

        DB::transaction(function () use ($organizationId, $userId, $register, $saleId, $payments) {
            foreach ($payments as $payment) {
                $mode   = (int) ($payment['payment_mode'] ?? 1);
                $amount = (float) ($payment['amount'] ?? 0);
                if ($amount <= 0) continue;

                $txnType = match($mode) {
                    1 => CashRegisterTransactionTypeEnum::CashSale->value,
                    2 => CashRegisterTransactionTypeEnum::UPISale->value,
                    3 => CashRegisterTransactionTypeEnum::CardSale->value,
                    4 => CashRegisterTransactionTypeEnum::BankTransferSale->value,
                    6 => CashRegisterTransactionTypeEnum::CreditSale->value,
                    default => CashRegisterTransactionTypeEnum::CashSale->value,
                };

                CashRegisterTransaction::create([
                    'organization_id'  => $organizationId,
                    'cash_register_id' => $register->id,
                    'user_id'          => $userId,
                    'transaction_type' => $txnType,
                    'source_type'      => CashRegisterSourceTypeEnum::Sale->value,
                    'source_id'        => $saleId,
                    'amount'           => $amount,
                    'payment_mode'     => $mode,
                    'created_by'       => $userId,
                ]);

                match($mode) {
                    1 => [$register->increment('cash_sales', $amount), $register->increment('expected_cash', $amount)],
                    2 => $register->increment('upi_sales', $amount),
                    3 => $register->increment('card_sales', $amount),
                    4 => $register->increment('bank_transfer_sales', $amount),
                    6 => $register->increment('credit_sales', $amount),
                    default => null,
                };
            }
            Cache::forget("org:{$organizationId}:cash-register:{$register->id}:summary");
        });
    }

    public function getOpenRegister(int $organizationId, int $userId): ?CashRegister
    {
        $key = $this->cacheKey($organizationId, $userId, 'open');
        $cached = Cache::get($key);
        if ($cached !== null) {
            return $cached === false ? null : CashRegister::find($cached);
        }
        $register = CashRegister::where('organization_id', $organizationId)
            ->where('user_id', $userId)
            ->where('register_status', CashRegisterStatusEnum::Open->value)
            ->latest()->first();
        Cache::put($key, $register ? $register->id : false, 300);
        return $register;
    }

    public function searchRegisters(int $organizationId, array $filters): array
    {
        $q = CashRegister::with('cashier:id,name')->where('organization_id', $organizationId);
        if (!empty($filters['user_id'])) $q->where('user_id', $filters['user_id']);
        if (!empty($filters['register_status'])) $q->where('register_status', $filters['register_status']);
        if (!empty($filters['date_from'])) $q->whereDate('opened_at', '>=', $filters['date_from']);
        if (!empty($filters['date_to'])) $q->whereDate('opened_at', '<=', $filters['date_to']);
        $perPage = $filters['per_page'] ?? 15;
        $page    = $filters['page'] ?? 1;
        $result  = $q->orderByDesc('opened_at')->paginate($perPage, ['*'], 'page', $page);
        return [
            'record'     => $result->items(),
            'page'       => $result->currentPage(),
            'total_page' => $result->lastPage(),
            'total_data' => $result->total(),
        ];
    }

    public function registerDetails(int $organizationId, int $registerId): CashRegister
    {
        return CashRegister::with(['cashier:id,name', 'transactions' => function($q) {
            $q->orderByDesc('created_at');
        }])->where('id', $registerId)->where('organization_id', $organizationId)->firstOrFail();
    }

    private function generateRegisterNo(int $organizationId): string
    {
        $prefix = 'REG-' . date('Ymd') . '-';
        $last = CashRegister::where('organization_id', $organizationId)
            ->where('register_no', 'like', $prefix . '%')
            ->orderByDesc('id')->first();
        $seq = $last ? ((int) substr($last->register_no, strlen($prefix))) + 1 : 1;
        return $prefix . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }

    private function createMismatchNotification(int $organizationId, CashRegister $register, float $diff, int $userId): void
    {
        $type    = $diff > 0 ? 'excess' : 'shortage';
        $amount  = abs($diff);
        $cashier = $register->cashier?->name ?? "User #{$register->user_id}";

        $this->notificationService->createForAdmins($organizationId, [
            'title'             => "Cash mismatch in {$cashier}'s register",
            'message'           => "Cash {$type} of ₹{$amount} found in {$cashier}'s register {$register->register_no}.",
            'notification_type' => NotificationTypeEnum::CashMismatch->value,
            'source_type'       => NotificationSourceTypeEnum::CashRegister->value,
            'source_id'         => $register->id,
            'priority'          => NotificationPriorityEnum::High->value,
            'action_url'        => "/cash-register/{$register->id}",
            'created_by'        => $userId,
        ]);
    }
}
