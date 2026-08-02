<?php

namespace App\Services;

use App\Models\Sale;
use Illuminate\Support\Collection;

class CustomerBalanceService
{
    public function attachSaleBalances(Collection $sales): void
    {
        if ($sales->isEmpty()) {
            return;
        }

        $customerIds = $sales->pluck('customer_id')->filter()->unique()->values();
        $saleBalanceMap = $this->buildSaleBalanceMap($customerIds);

        foreach ($sales as $sale) {
            $fallbackPaid = (float) ($sale->paid_amount ?? 0);
            $fallbackTotal = (float) ($sale->total_amount ?? 0);

            $balance = $saleBalanceMap[$sale->id] ?? [
                'db_paid_amount' => round($fallbackPaid, 2),
                'adj_paid_amount' => 0.0,
                'paid_amount' => round($fallbackPaid, 2),
                'adj_bill_number' => null,
                'previous_balance' => 0.0,
                'invoice_previous_balance' => 0.0,
                'total_payable' => round($fallbackTotal, 2),
                'remaining_balance_due' => round(max(0, $fallbackTotal - $fallbackPaid), 2),
            ];

            foreach ($balance as $key => $value) {
                $sale->setAttribute($key, $value);
            }
        }
    }

    public function attachInvoiceBalances(Collection $invoices): void
    {
        if ($invoices->isEmpty()) {
            return;
        }

        $customerIds = $invoices->pluck('customer_id')->filter()->unique()->values();
        $saleBalanceMap = $this->buildSaleBalanceMap($customerIds);

        foreach ($invoices as $invoice) {
            $fallbackPaid = (float) ($invoice->paid_amount ?? 0);
            $fallbackTotal = (float) ($invoice->total_amount ?? 0);

            $balance = $saleBalanceMap[$invoice->sale_id] ?? [
                'db_paid_amount' => round($fallbackPaid, 2),
                'adj_paid_amount' => 0.0,
                'calculated_paid_amount' => round($fallbackPaid, 2),
                'invoice_previous_balance' => 0.0,
                'total_payable' => round($fallbackTotal, 2),
                'remaining_balance_due' => round(max(0, $fallbackTotal - $fallbackPaid), 2),
            ];

            foreach ($balance as $key => $value) {
                $invoice->setAttribute($key, $value);
            }
        }
    }

    public function calculateCustomerBalanceSummary(int $customerId, ?int $excludeSaleId = null): array
    {
        $salesQuery = Sale::query()
            ->where('customer_id', $customerId)
            ->where('sale_number', 'not like', 'ADJ-%');

        if ($excludeSaleId) {
            $salesQuery->where('id', '!=', $excludeSaleId);
        }

        $sales = $salesQuery
            ->orderBy('sale_date')
            ->orderBy('id')
            ->get(['id', 'sale_number', 'total_amount', 'paid_amount']);

        if ($sales->isEmpty()) {
            return [
                'total_price' => 0.0,
                'paid_amount' => 0.0,
                'unpaid_amount' => 0.0,
            ];
        }

        $excludedSaleNumber = null;
        if ($excludeSaleId) {
            $excludedSaleNumber = Sale::query()->where('id', $excludeSaleId)->value('sale_number');
        }

        [$adjPaidBySaleNumber] = $this->buildAdjMaps(collect([$customerId]), $excludedSaleNumber);

        $cumulativeTotal = 0.0;
        $cumulativePaid = 0.0;

        foreach ($sales as $sale) {
            $cumulativeTotal += (float) ($sale->total_amount ?? 0);
            $dbPaid = (float) ($sale->paid_amount ?? 0);
            $adjPaid = (float) ($adjPaidBySaleNumber[$customerId][$sale->sale_number] ?? 0);
            $remainingAfterDbPaid = max(0.0, ((float) ($sale->total_amount ?? 0)) - $dbPaid);
            $effectiveAdjPaid = min($adjPaid, $remainingAfterDbPaid);
            $cumulativePaid += ($dbPaid + $effectiveAdjPaid);

            if ($cumulativePaid > $cumulativeTotal) {
                $cumulativePaid = $cumulativeTotal;
            }
        }

        return [
            'total_price' => round($cumulativeTotal, 2),
            'paid_amount' => round($cumulativePaid, 2),
            'unpaid_amount' => round(max(0, $cumulativeTotal - $cumulativePaid), 2),
        ];
    }

    protected function buildSaleBalanceMap(Collection $customerIds): array
    {
        if ($customerIds->isEmpty()) {
            return [];
        }

        $sales = Sale::query()
            ->whereIn('customer_id', $customerIds)
            ->where('sale_number', 'not like', 'ADJ-%')
            ->orderBy('customer_id')
            ->orderBy('sale_date')
            ->orderBy('id')
            ->get(['id', 'customer_id', 'sale_number', 'total_amount', 'paid_amount']);

        [$adjPaidBySaleNumber, $adjBillNumberBySaleNumber] = $this->buildAdjMaps($customerIds);

        $saleBalanceMap = [];
        $grouped = $sales->groupBy('customer_id');

        foreach ($grouped as $customerId => $customerSales) {
            $cumulativeTotal = 0.0;
            $cumulativePaid = 0.0;

            foreach ($customerSales as $sale) {
                $previousBalance = round(max(0, $cumulativeTotal - $cumulativePaid), 2);

                $saleTotal = (float) ($sale->total_amount ?? 0);
                $dbPaid = (float) ($sale->paid_amount ?? 0);
                $adjPaid = (float) ($adjPaidBySaleNumber[(int) $customerId][$sale->sale_number] ?? 0);
                $remainingAfterDbPaid = max(0.0, $saleTotal - $dbPaid);
                $effectiveAdjPaid = min($adjPaid, $remainingAfterDbPaid);
                $mergedPaid = $dbPaid + $effectiveAdjPaid;

                $cumulativeTotal += $saleTotal;
                $cumulativePaid += $mergedPaid;

                if ($cumulativePaid > $cumulativeTotal) {
                    $cumulativePaid = $cumulativeTotal;
                }

                $saleBalanceMap[$sale->id] = [
                    'db_paid_amount' => round($dbPaid, 2),
                    'adj_paid_amount' => round($adjPaid, 2),
                    'paid_amount' => round($mergedPaid, 2),
                    'calculated_paid_amount' => round($mergedPaid, 2),
                    'adj_bill_number' => $adjBillNumberBySaleNumber[(int) $customerId][$sale->sale_number] ?? null,
                    'previous_balance' => $previousBalance,
                    'invoice_previous_balance' => $previousBalance,
                    'total_payable' => round($saleTotal + $previousBalance, 2),
                    'remaining_balance_due' => round(max(0, $cumulativeTotal - $cumulativePaid), 2),
                ];
            }
        }

        return $saleBalanceMap;
    }

    protected function buildAdjMaps(Collection $customerIds, ?string $excludeParentSaleNumber = null): array
    {
        $adjBills = Sale::query()
            ->whereIn('customer_id', $customerIds)
            ->where('sale_number', 'like', 'ADJ-%')
            ->get(['customer_id', 'sale_number', 'notes', 'paid_amount']);

        $adjPaidBySaleNumber = [];
        $adjBillNumberBySaleNumber = [];

        foreach ($adjBills as $adjBill) {
            if (!$adjBill->notes || !preg_match('/Sale:\s*(\S+)/', $adjBill->notes, $matches)) {
                continue;
            }

            $parentSaleNumber = $matches[1];
            if ($excludeParentSaleNumber && $parentSaleNumber === $excludeParentSaleNumber) {
                continue;
            }

            $customerId = (int) $adjBill->customer_id;
            $adjPaidBySaleNumber[$customerId][$parentSaleNumber] =
                ($adjPaidBySaleNumber[$customerId][$parentSaleNumber] ?? 0)
                + (float) ($adjBill->paid_amount ?? 0);

            $adjBillNumberBySaleNumber[$customerId][$parentSaleNumber] = $adjBill->sale_number;
        }

        return [$adjPaidBySaleNumber, $adjBillNumberBySaleNumber];
    }
}
