<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerPaymentLog;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class RepairIqbalKhanBakerySep2PaymentService
{
    public const SALE_NUMBER = 'SALE-009879';

    public const CUSTOMER_NAME = 'IQBAL KHAN BAKERY / OCH NAIR';

    public const REQUESTED_PAID = 197000.00;

    /**
     * Restore the 2 Sep offline cash that sync capped at the bill total.
     *
     * @return array{status: string, message: string, extra_applied: float, remaining_after: float|null}
     */
    public function repair(bool $dryRun = true): array
    {
        $sale = Sale::withoutGlobalScopes()
            ->where('sale_number', self::SALE_NUMBER)
            ->first();

        if (! $sale) {
            return [
                'status' => 'skipped',
                'message' => self::SALE_NUMBER.' was not found.',
                'extra_applied' => 0.0,
                'remaining_after' => null,
            ];
        }

        $customer = Customer::withoutGlobalScopes()->find($sale->customer_id);
        if (! $customer || strcasecmp(trim((string) $customer->name), self::CUSTOMER_NAME) !== 0) {
            return [
                'status' => 'skipped',
                'message' => self::SALE_NUMBER.' does not belong to '.self::CUSTOMER_NAME.'.',
                'extra_applied' => 0.0,
                'remaining_after' => null,
            ];
        }

        $paidTowardSale = round((float) $sale->paid_amount, 2);
        $extraPayment = round(max(0, self::REQUESTED_PAID - (float) $sale->total_amount), 2);

        $hasCheckoutLog = CustomerPaymentLog::withoutGlobalScopes()
            ->where('sale_id', $sale->id)
            ->whereIn('log_type', ['cash_received', 'payment'])
            ->where('description', 'like', '%received for Sale: '.self::SALE_NUMBER.'%')
            ->exists();

        $hasExtraAdj = Sale::withoutGlobalScopes()
            ->where('customer_id', $sale->customer_id)
            ->where('sale_number', 'like', 'ADJ-%')
            ->where('notes', 'like', '%Extra payment from Sale: '.self::SALE_NUMBER.'%')
            ->exists();

        if ($hasCheckoutLog && $hasExtraAdj) {
            return [
                'status' => 'already',
                'message' => self::SALE_NUMBER.' already has the 2 Sep cash and extra payment recorded.',
                'extra_applied' => 0.0,
                'remaining_after' => $this->customerRemaining((int) $sale->customer_id),
            ];
        }

        if ($dryRun) {
            return [
                'status' => 'dry_run',
                'message' => sprintf(
                    'Would record PKR %s cash on %s and apply PKR %s to older unpaid bills.',
                    number_format($paidTowardSale, 2),
                    self::SALE_NUMBER,
                    number_format($extraPayment, 2)
                ),
                'extra_applied' => $extraPayment,
                'remaining_after' => null,
            ];
        }

        DB::transaction(function () use ($sale, $paidTowardSale, $extraPayment, $hasCheckoutLog, $hasExtraAdj) {
            $payments = app(ApplyCustomerSalePaymentService::class);
            $occurredAt = $sale->created_at;

            if (! $hasCheckoutLog && $paidTowardSale > 0.009) {
                $payments->recordCheckout(
                    $sale,
                    $paidTowardSale,
                    0,
                    (int) $sale->user_id,
                    'pos order',
                    $occurredAt
                );
            }

            if (! $hasExtraAdj && $extraPayment > 0.009) {
                $payments->applyExtraTowardPreviousBalance(
                    $sale,
                    $extraPayment,
                    (int) $sale->user_id,
                    'pos order',
                    $occurredAt
                );
            }
        });

        return [
            'status' => 'repaired',
            'message' => sprintf(
                'Recorded PKR %s on %s and applied PKR %s to older unpaid bills.',
                number_format($paidTowardSale, 2),
                self::SALE_NUMBER,
                number_format($hasExtraAdj ? 0 : $extraPayment, 2)
            ),
            'extra_applied' => $hasExtraAdj ? 0.0 : $extraPayment,
            'remaining_after' => $this->customerRemaining((int) $sale->customer_id),
        ];
    }

    protected function customerRemaining(int $customerId): float
    {
        return app(CustomerBalanceService::class)->calculateCustomerBalanceSummary($customerId)['unpaid_amount'];
    }
}
