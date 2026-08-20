<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Unit;
use App\Models\User;
use App\Support\CurrentBranch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class RestoreMissedOfflineSaleService
{
    /**
     * Restore one historical offline POS sale that the server rejected.
     *
     * @param  array<string, mixed>  $record
     * @return array{
     *     status: string,
     *     message: string,
     *     sale_id: int|null,
     *     sale_number: string|null,
     *     total: float,
     *     item_count: int,
     *     warnings: list<string>,
     *     items: list<array<string, mixed>>
     * }
     */
    public function restore(array $record, bool $dryRun = true): array
    {
        $uuid = (string) ($record['client_uuid'] ?? '');
        $customerId = (int) ($record['customer_id'] ?? 0);
        $expectedName = trim((string) ($record['customer_name'] ?? ''));
        $branchId = (int) ($record['branch_id'] ?? CurrentBranch::DEFAULT_BRANCH_ID);
        $userId = (int) ($record['user_id'] ?? 0);
        $items = $record['items'] ?? [];
        $expectedTotal = round((float) ($record['expected_total'] ?? 0), 2);

        if ($uuid === '' || $customerId < 1 || $expectedName === '' || $userId < 1) {
            throw new RuntimeException('Restore payload is missing client_uuid, customer_id, customer_name, or user_id.');
        }

        if (! is_array($items) || $items === []) {
            throw new RuntimeException('Restore payload has no sale items.');
        }

        $computed = $this->computeTotals($items);
        if (abs($computed['total'] - $expectedTotal) > 0.009) {
            throw new RuntimeException(
                "Restore payload total mismatch: items sum to {$computed['total']}, expected {$expectedTotal}."
            );
        }

        $customer = Customer::withoutGlobalScopes()->find($customerId);
        if (! $customer) {
            throw new RuntimeException("Customer {$customerId} was not found.");
        }

        if (strcasecmp(trim((string) $customer->name), $expectedName) !== 0) {
            throw new RuntimeException(
                "Customer {$customerId} is '{$customer->name}', expected '{$expectedName}'."
            );
        }

        if ((int) $customer->branch_id !== $branchId) {
            throw new RuntimeException(
                "Customer {$customerId} belongs to branch {$customer->branch_id}, expected {$branchId}."
            );
        }

        if (! User::query()->whereKey($userId)->exists()) {
            throw new RuntimeException("User {$userId} was not found.");
        }

        $existing = $this->existingRestore($uuid, $customerId, $record['sale_date'] ?? null, $expectedTotal);
        if ($existing) {
            return [
                'status' => 'already_restored',
                'message' => "Sale {$existing['sale_number']} is already on the ledger.",
                'sale_id' => $existing['sale_id'],
                'sale_number' => $existing['sale_number'],
                'total' => $expectedTotal,
                'item_count' => count($items),
                'warnings' => [],
                'items' => $computed['lines'],
            ];
        }

        $warnings = [];
        $prepared = [];
        foreach ($items as $index => $line) {
            $prepared[] = $this->prepareLine($line, $index, $branchId, $warnings);
        }

        if ($dryRun) {
            return [
                'status' => 'dry_run',
                'message' => 'Dry run only. No sale was written.',
                'sale_id' => null,
                'sale_number' => null,
                'total' => $expectedTotal,
                'item_count' => count($prepared),
                'warnings' => $warnings,
                'items' => $computed['lines'],
            ];
        }

        return DB::transaction(function () use (
            $record,
            $uuid,
            $customerId,
            $branchId,
            $userId,
            $expectedTotal,
            $computed,
            $prepared,
            $warnings
        ) {
            $again = $this->existingRestore($uuid, $customerId, $record['sale_date'] ?? null, $expectedTotal);
            if ($again) {
                return [
                    'status' => 'already_restored',
                    'message' => "Sale {$again['sale_number']} is already on the ledger.",
                    'sale_id' => $again['sale_id'],
                    'sale_number' => $again['sale_number'],
                    'total' => $expectedTotal,
                    'item_count' => count($prepared),
                    'warnings' => $warnings,
                    'items' => $computed['lines'],
                ];
            }

            $saleDate = (string) ($record['sale_date'] ?? '2026-08-13');
            $createdAt = (string) ($record['created_at'] ?? $saleDate.' 16:49:59');
            $paidAmount = round((float) ($record['paid_amount'] ?? 0), 2);
            $paymentStatus = $paidAmount <= 0
                ? 'partial'
                : ($paidAmount + 0.009 < $expectedTotal ? 'partial' : 'paid');

            $saleNumber = Sale::generateSaleNumber('SALE', $branchId);
            $sale = Sale::withoutGlobalScopes()->create([
                'branch_id' => $branchId,
                'sale_number' => $saleNumber,
                'customer_id' => $customerId,
                'user_id' => $userId,
                'sale_date' => $saleDate,
                'subtotal' => $expectedTotal,
                'tax_amount' => 0,
                'discount_amount' => $computed['discount'],
                'total_amount' => $expectedTotal,
                'paid_amount' => $paidAmount,
                'payment_status' => $paymentStatus,
                'status' => 'completed',
                'notes' => $record['notes'] ?? "Customer: {$record['customer_name']}",
            ]);

            DB::table('sales')->where('id', $sale->id)->update([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            foreach ($prepared as $line) {
                $item = SaleItem::create([
                    'sale_id' => $sale->id,
                    'branch_id' => $branchId,
                    'product_id' => $line['product_id'],
                    'product_name' => $line['product_name'],
                    'quantity' => $line['quantity'],
                    'quantity_in_base_unit' => $line['quantity'],
                    'unit_id' => $line['unit_id'],
                    'unit_price' => $line['selling_price'],
                    'discount' => $line['discount_amount'],
                    'tax' => 0,
                    'total' => $line['line_total'],
                ]);

                DB::table('sale_items')->where('id', $item->id)->update([
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                if ($line['product_id'] && $line['deduct'] > 0) {
                    $product = Product::query()->find($line['product_id']);
                    if ($product) {
                        try {
                            $product->decrementStock($line['deduct'], $branchId, [
                                'source_type' => 'sale',
                                'source_id' => $sale->id,
                                'reason' => 'restore missed offline sale '.$uuid,
                                'idempotency_key' => 'restore-sale-'.$uuid.'-'.$product->id,
                            ]);
                        } catch (InsufficientStockException $e) {
                            $available = $product->currentStock($branchId);
                            if ($available > 0.000001) {
                                $product->decrementStock($available, $branchId, [
                                    'source_type' => 'sale',
                                    'source_id' => $sale->id,
                                    'reason' => 'restore missed offline sale '.$uuid.' (partial)',
                                    'idempotency_key' => 'restore-sale-'.$uuid.'-'.$product->id,
                                ]);
                            }
                            $warnings[] = $e->getMessage();
                        }
                    }
                }
            }

            $this->mapUuid($uuid, $sale->id, $saleNumber, $branchId);

            return [
                'status' => 'created',
                'message' => "Created {$saleNumber} for {$expectedTotal}.",
                'sale_id' => $sale->id,
                'sale_number' => $saleNumber,
                'total' => $expectedTotal,
                'item_count' => count($prepared),
                'warnings' => $warnings,
                'items' => $computed['lines'],
            ];
        });
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array{total: float, discount: float, lines: list<array<string, mixed>>}
     */
    public function computeTotals(array $items): array
    {
        $total = 0.0;
        $discount = 0.0;
        $lines = [];

        foreach ($items as $line) {
            $qty = (float) ($line['quantity'] ?? 0);
            $price = (float) ($line['selling_price'] ?? 0);
            $lineTotal = round($qty * $price, 2);
            $lineDiscount = 0.0;
            if (! empty($line['discount']) && (float) $line['discount'] > 0) {
                if (($line['discount_type'] ?? '') === 'percentage') {
                    $lineDiscount = round($lineTotal * ((float) $line['discount'] / 100), 2);
                } else {
                    $lineDiscount = round((float) $line['discount'], 2);
                }
                $lineTotal = round($lineTotal - $lineDiscount, 2);
            }

            $total += $lineTotal;
            $discount += $lineDiscount;
            $lines[] = [
                'product_id' => $line['product_id'] ?? null,
                'product_name' => $line['product_name'] ?? null,
                'quantity' => $qty,
                'selling_price' => $price,
                'line_total' => $lineTotal,
            ];
        }

        return [
            'total' => round($total, 2),
            'discount' => round($discount, 2),
            'lines' => $lines,
        ];
    }

    /**
     * @param  array<string, mixed>  $line
     * @param  list<string>  $warnings
     * @return array<string, mixed>
     */
    protected function prepareLine(array $line, int $index, int $branchId, array &$warnings): array
    {
        $qty = (float) ($line['quantity'] ?? 0);
        $price = (float) ($line['selling_price'] ?? 0);
        $lineTotal = round($qty * $price, 2);
        $discountAmount = 0.0;
        if (! empty($line['discount']) && (float) $line['discount'] > 0) {
            if (($line['discount_type'] ?? '') === 'percentage') {
                $discountAmount = round($lineTotal * ((float) $line['discount'] / 100), 2);
            } else {
                $discountAmount = round((float) $line['discount'], 2);
            }
            $lineTotal = round($lineTotal - $discountAmount, 2);
        }

        $isCustom = ! empty($line['is_custom']);
        $productId = $isCustom ? null : (! empty($line['product_id']) ? (int) $line['product_id'] : null);
        $productName = trim((string) ($line['product_name'] ?? ''));
        $unitId = ! empty($line['unit_id']) ? (int) $line['unit_id'] : null;
        $deduct = 0.0;

        if ($unitId && ! Unit::query()->whereKey($unitId)->exists()) {
            $warnings[] = "Line ".($index + 1)." unit_id {$unitId} is missing; stored without a unit.";
            $unitId = null;
        }

        if ($productId) {
            $product = Product::query()->find($productId);
            if (! $product) {
                $warnings[] = "Line ".($index + 1)." product_id {$productId} is missing; stored as custom ({$productName}).";
                $productId = null;
            } else {
                if ($productName === '') {
                    $productName = (string) $product->name;
                }
                $available = (float) ($product->currentStock($branchId) ?? 0);
                $deduct = min($qty, max(0, $available));
                if ($deduct + 0.000001 < $qty) {
                    $warnings[] = "Line ".($index + 1)." {$product->name}: deducting {$deduct} of {$qty} (available {$available}).";
                }
            }
        }

        if ($productName === '') {
            throw new RuntimeException('Line '.($index + 1).' has no product name.');
        }

        if ($qty <= 0) {
            throw new RuntimeException('Line '.($index + 1).' has invalid quantity.');
        }

        return [
            'product_id' => $productId,
            'product_name' => $productId ? null : $productName,
            'quantity' => $qty,
            'unit_id' => $unitId,
            'selling_price' => $price,
            'discount_amount' => $discountAmount,
            'line_total' => $lineTotal,
            'deduct' => $deduct,
        ];
    }

    /**
     * @return array{sale_id: int, sale_number: string}|null
     */
    protected function existingRestore(string $uuid, int $customerId, mixed $saleDate, float $total): ?array
    {
        if (Schema::hasTable('sync_id_mappings')) {
            $mapped = DB::table('sync_id_mappings')
                ->where('client_uuid', $uuid)
                ->where('entity', 'sale')
                ->first();

            if ($mapped) {
                $sale = Sale::withoutGlobalScopes()->find($mapped->server_id);
                if ($sale) {
                    return [
                        'sale_id' => (int) $sale->id,
                        'sale_number' => (string) $sale->sale_number,
                    ];
                }
            }
        }

        $query = Sale::withoutGlobalScopes()
            ->where('customer_id', $customerId)
            ->where('total_amount', $total);

        if ($saleDate) {
            $query->whereDate('sale_date', $saleDate);
        }

        $sale = $query->orderBy('id')->first();
        if ($sale) {
            return [
                'sale_id' => (int) $sale->id,
                'sale_number' => (string) $sale->sale_number,
            ];
        }

        return null;
    }

    protected function mapUuid(string $uuid, int $saleId, string $saleNumber, int $branchId): void
    {
        if (! Schema::hasTable('sync_id_mappings')) {
            return;
        }

        $row = [
            'client_uuid' => $uuid,
            'entity' => 'sale',
            'server_id' => (string) $saleId,
            'meta' => json_encode(['sale_number' => $saleNumber]),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('sync_id_mappings', 'branch_id')) {
            $row['branch_id'] = $branchId;
        }

        DB::table('sync_id_mappings')->insert($row);
    }
}
