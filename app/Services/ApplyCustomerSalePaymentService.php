<?php

namespace App\Services;

use App\Models\CustomerPaymentLog;
use App\Models\Sale;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class ApplyCustomerSalePaymentService
{
    /**
     * Record the cash taken at checkout and apply any surplus to older unpaid bills.
     * Sale.paid_amount must already be capped at the current bill total.
     */
    public function recordCheckout(
        Sale $sale,
        float $paidTowardSale,
        float $extraPayment,
        int $userId,
        ?string $comment = null,
        ?CarbonInterface $occurredAt = null
    ): void {
        $paidTowardSale = round($paidTowardSale, 2);
        $extraPayment = round(max(0, $extraPayment), 2);

        if ($sale->customer_id && $paidTowardSale > 0.009) {
            $this->createLog([
                'branch_id' => $sale->branch_id,
                'customer_id' => $sale->customer_id,
                'user_id' => $userId,
                'log_type' => 'cash_received',
                'sale_id' => $sale->id,
                'reference_number' => $sale->sale_number,
                'amount' => $paidTowardSale,
                'previous_amount' => 0,
                'new_amount' => $paidTowardSale,
                'payment_status' => $sale->payment_status,
                'description' => 'Cash received for Sale: '.$sale->sale_number.'. Amount: PKR '.number_format($paidTowardSale, 2),
                'comment' => $comment,
            ], $occurredAt);
        }

        if ($sale->customer_id && $extraPayment > 0.009) {
            $this->applyExtraTowardPreviousBalance($sale, $extraPayment, $userId, $comment, $occurredAt);
        }
    }

    public function applyExtraTowardPreviousBalance(
        Sale $sale,
        float $extraPayment,
        int $userId,
        ?string $comment = null,
        ?CarbonInterface $occurredAt = null
    ): void {
        $extraPayment = round($extraPayment, 2);
        if ($extraPayment <= 0.009 || ! $sale->customer_id) {
            return;
        }

        $adjSale = Sale::withoutGlobalScopes()->create([
            'branch_id' => $sale->branch_id,
            'sale_number' => Sale::generateSaleNumber('ADJ', (int) $sale->branch_id),
            'customer_id' => $sale->customer_id,
            'user_id' => $userId,
            'sale_date' => $occurredAt?->toDateString() ?? now()->toDateString(),
            'subtotal' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 0,
            'paid_amount' => $extraPayment,
            'payment_status' => 'paid',
            'status' => 'completed',
            'notes' => 'Previous balance payment - Extra payment from Sale: '.$sale->sale_number,
        ]);

        $this->stampCreatedAt($adjSale->getTable(), $adjSale->id, $occurredAt);

        $pendingSales = Sale::withoutGlobalScopes()
            ->where('customer_id', $sale->customer_id)
            ->where('id', '!=', $sale->id)
            ->where('id', '!=', $adjSale->id)
            ->where(function ($query) {
                $query->where('payment_status', 'pending')
                    ->orWhere('payment_status', 'partial');
            })
            ->where('sale_number', 'not like', 'ADJ-%')
            ->where(function ($query) use ($sale) {
                $saleDate = $sale->sale_date?->toDateString() ?? (string) $sale->sale_date;
                $query->whereDate('sale_date', '<', $saleDate)
                    ->orWhere(function ($sameDay) use ($sale, $saleDate) {
                        $sameDay->whereDate('sale_date', $saleDate)
                            ->where('id', '<', $sale->id);
                    });
            })
            ->orderBy('sale_date')
            ->orderBy('id')
            ->get();

        $remainingPayment = $extraPayment;
        foreach ($pendingSales as $pendingSale) {
            if ($remainingPayment <= 0.009) {
                break;
            }

            $currentPaid = (float) ($pendingSale->paid_amount ?? 0);
            $remainingBalance = (float) $pendingSale->total_amount - $currentPaid;
            if ($remainingBalance <= 0.009) {
                continue;
            }

            $paymentToApply = round(min($remainingPayment, $remainingBalance), 2);
            $newPaidAmount = round($currentPaid + $paymentToApply, 2);
            $newPaymentStatus = $newPaidAmount + 0.009 >= (float) $pendingSale->total_amount
                ? 'paid'
                : 'partial';

            $pendingSale->update([
                'paid_amount' => $newPaidAmount,
                'payment_status' => $newPaymentStatus,
            ]);

            $this->createLog([
                'branch_id' => $sale->branch_id,
                'customer_id' => $sale->customer_id,
                'user_id' => $userId,
                'log_type' => 'payment',
                'sale_id' => $pendingSale->id,
                'reference_number' => $pendingSale->sale_number,
                'amount' => $paymentToApply,
                'previous_amount' => $currentPaid,
                'new_amount' => $newPaidAmount,
                'payment_status' => $newPaymentStatus,
                'description' => 'Previous balance payment from Sale: '.$sale->sale_number.'. Applied PKR '.number_format($paymentToApply, 2).' to Sale: '.$pendingSale->sale_number,
                'comment' => $comment,
            ], $occurredAt);

            $remainingPayment = round($remainingPayment - $paymentToApply, 2);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createLog(array $attributes, ?CarbonInterface $occurredAt = null): CustomerPaymentLog
    {
        $log = CustomerPaymentLog::withoutGlobalScopes()->create($attributes);
        $this->stampCreatedAt($log->getTable(), $log->id, $occurredAt);

        return $log;
    }

    protected function stampCreatedAt(string $table, int $id, ?CarbonInterface $occurredAt): void
    {
        if (! $occurredAt) {
            return;
        }

        DB::table($table)->where('id', $id)->update([
            'created_at' => $occurredAt,
            'updated_at' => $occurredAt,
        ]);
    }
}
