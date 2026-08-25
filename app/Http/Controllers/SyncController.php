<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchProductStock;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\UnitConversion;
use App\Models\User;
use App\Services\UnitConversionService;
use App\Support\CurrentBranch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class SyncController extends Controller
{
    public function ping()
    {
        return response()->json([
            'ok' => true,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function enrollVault(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        /** @var User $user */
        $user = $request->user();

        if (! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Password does not match the signed-in account.'],
            ]);
        }

        return response()->json([
            'password_hash' => $user->password,
            'user' => $this->userPayload($user),
        ]);
    }

    public function bootstrap(Request $request)
    {
        $user = $request->user();
        $branchId = $this->snapshotBranchId($user);

        return response()->json($this->fullSnapshot($user, $branchId));
    }

    public function pull(Request $request)
    {
        $user = $request->user();
        $branchId = $this->snapshotBranchId($user);
        $since = $request->query('since');

        // Simple strategy: if since is missing/stale, return full snapshot.
        $sinceAt = null;
        try {
            $sinceAt = $since ? \Carbon\Carbon::parse($since) : null;
        } catch (\Throwable) {
            $sinceAt = null;
        }

        if (! $sinceAt || now()->diffInHours($sinceAt) > 24) {
            $payload = $this->fullSnapshot($user, $branchId);
            $payload['full'] = true;

            return response()->json($payload);
        }

        $payload = [
            'full' => false,
            'server_time' => now()->toIso8601String(),
            'active_branch_id' => $branchId,
            'cache_version' => 2,
            'user' => $this->userPayload($user),
            'products' => $this->productsForBranch($branchId, $sinceAt),
            'categories' => Category::query()->where('updated_at', '>', $sinceAt)->get(),
            'units' => Unit::query()->where('updated_at', '>', $sinceAt)->get(),
            'customers' => Customer::query()->where('updated_at', '>', $sinceAt)->get(),
            'suppliers' => Supplier::query()->where('updated_at', '>', $sinceAt)->get(),
            'sales' => Sale::query()->where('updated_at', '>', $sinceAt)->limit(500)->get(),
            'sale_items' => SaleItem::query()
                ->whereIn('sale_id', Sale::query()->where('updated_at', '>', $sinceAt)->limit(500)->pluck('id'))
                ->get(),
            'orders' => Order::query()->where('updated_at', '>', $sinceAt)->limit(500)->get(),
            'expenses' => Expense::query()->where('updated_at', '>', $sinceAt)->limit(500)->get(),
            'invoices' => Invoice::query()->where('updated_at', '>', $sinceAt)->limit(500)->get(),
            'branches' => $user->isAdmin()
                ? Branch::query()->orderBy('name')->get()
                : Branch::query()->where('id', $branchId)->get(),
            'product_units' => ProductUnit::query()->where('updated_at', '>', $sinceAt)->get(),
            'unit_conversions' => UnitConversion::query()->where('updated_at', '>', $sinceAt)->get(),
            'branch_stocks' => BranchProductStock::query()
                ->where('branch_id', $branchId)
                ->where('updated_at', '>', $sinceAt)
                ->get()
                ->map(fn ($row) => [
                    'branch_id' => $row->branch_id,
                    'product_id' => $row->product_id,
                    'stock_quantity' => (float) $row->stock_quantity,
                    'quantity' => (float) $row->stock_quantity,
                    'selling_type' => $row->selling_type,
                    'display_name' => $row->display_name,
                    'purchase_price' => $row->purchase_price,
                    'selling_price' => $row->selling_price,
                    'retail_price' => $row->retail_price,
                    'wholesale_price' => $row->wholesale_price,
                    'updated_at' => $row->updated_at,
                ]),
            'deleted' => new \stdClass(),
        ];

        return response()->json($payload);
    }

    public function push(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.client_uuid' => ['required', 'uuid'],
            'items.*.entity' => ['required', 'string'],
            'items.*.op' => ['required', 'string'],
            'items.*.payload' => ['required', 'array'],
            'items.*.branch_id' => ['nullable', 'integer'],
            'items.*.created_at' => ['nullable', 'string'],
        ]);

        $results = [];

        foreach ($validated['items'] as $item) {
            $results[] = $this->applyPushItem($user, $item);
        }

        return response()->json([
            'results' => $results,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    protected function resolvedBranchId(User $user, array $item): int
    {
        if (! $user->isAdmin()) {
            $branchId = CurrentBranch::id($user);
            if (! $branchId) {
                throw new \RuntimeException('No branch assigned.');
            }

            return $branchId;
        }

        $requested = $item['branch_id'] ?? CurrentBranch::id($user);
        if (! $requested) {
            throw new \RuntimeException('Select a branch before syncing.');
        }

        return (int) $requested;
    }

    protected function applyPushItem(User $user, array $item): array
    {
        $uuid = $item['client_uuid'];

        if (Schema::hasTable('sync_id_mappings')) {
            $existingQuery = DB::table('sync_id_mappings')->where('client_uuid', $uuid);
            $branchId = CurrentBranch::id($user);
            if ($branchId) {
                $existingQuery->where(function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId)->orWhereNull('branch_id');
                });
            }
            $existing = $existingQuery->first();
            if ($existing) {
                $meta = is_string($existing->meta)
                    ? (json_decode($existing->meta, true) ?: [])
                    : (array) ($existing->meta ?? []);

                return [
                    'client_uuid' => $uuid,
                    'entity' => $item['entity'],
                    'status' => 'ok',
                    'server_id' => $existing->server_id,
                    'sale_number' => $meta['sale_number'] ?? null,
                ];
            }
        }

        try {
            return DB::transaction(function () use ($user, $item, $uuid) {
                return match ($item['entity']) {
                    'customer' => $this->pushCustomer($user, $item, $uuid),
                    'expense' => $this->pushExpense($user, $item, $uuid),
                    'supplier' => $this->pushSupplier($user, $item, $uuid),
                    'sale' => $this->pushSale($user, $item, $uuid),
                    default => [
                        'client_uuid' => $uuid,
                        'entity' => $item['entity'],
                        'status' => 'conflict',
                        'message' => 'Unsupported entity for offline sync: '.$item['entity'],
                    ],
                };
            });
        } catch (\Throwable $e) {
            return [
                'client_uuid' => $uuid,
                'entity' => $item['entity'],
                'status' => 'conflict',
                'message' => $e->getMessage(),
            ];
        }
    }

    protected function pushCustomer(User $user, array $item, string $uuid): array
    {
        $payload = $item['payload'];
        $branchId = $this->resolvedBranchId($user, $item);
        $customer = Customer::create([
            'name' => $payload['name'] ?? 'Customer',
            'phone' => $payload['phone'] ?? null,
            'email' => $payload['email'] ?? null,
            'address' => $payload['address'] ?? null,
            'customer_type' => $payload['customer_type'] ?? 'retail',
            'branch_id' => $branchId,
            'is_active' => true,
        ]);

        $this->mapUuid($uuid, 'customer', $customer->id, [], $branchId);

        return [
            'client_uuid' => $uuid,
            'entity' => 'customer',
            'status' => 'ok',
            'server_id' => $customer->id,
        ];
    }

    protected function pushExpense(User $user, array $item, string $uuid): array
    {
        $payload = $item['payload'];
        $branchId = $this->resolvedBranchId($user, $item);
        $expense = Expense::create([
            'name' => $payload['name'] ?? $payload['title'] ?? 'Expense',
            'amount' => $payload['amount'] ?? 0,
            'category' => $payload['category'] ?? null,
            'description' => $payload['description'] ?? $payload['notes'] ?? null,
            'expense_date' => $payload['expense_date'] ?? now()->toDateString(),
            'branch_id' => $branchId,
            'user_id' => $user->id,
        ]);

        $this->mapUuid($uuid, 'expense', $expense->id, [], $branchId);

        return [
            'client_uuid' => $uuid,
            'entity' => 'expense',
            'status' => 'ok',
            'server_id' => $expense->id,
        ];
    }

    protected function pushSupplier(User $user, array $item, string $uuid): array
    {
        $payload = $item['payload'];
        $branchId = $this->resolvedBranchId($user, $item);
        $email = isset($payload['email']) && trim((string) $payload['email']) !== ''
            ? trim((string) $payload['email'])
            : null;

        $supplier = Supplier::create([
            'branch_id' => $branchId,
            'supplier_id' => ! empty($payload['supplier_id']) ? $payload['supplier_id'] : null,
            'name' => $payload['name'] ?? 'Supplier',
            'company_name' => $payload['company_name'] ?? null,
            'email' => $email,
            'phone' => $payload['phone'] ?? null,
            'address' => $payload['address'] ?? null,
            'city' => $payload['city'] ?? null,
            'state' => $payload['state'] ?? null,
            'country' => $payload['country'] ?? null,
            'postal_code' => $payload['postal_code'] ?? null,
            'tax_id' => $payload['tax_id'] ?? null,
            'is_active' => true,
        ]);

        $this->mapUuid($uuid, 'supplier', $supplier->id, [
            'supplier_id' => $supplier->supplier_id,
        ], $branchId);

        return [
            'client_uuid' => $uuid,
            'entity' => 'supplier',
            'status' => 'ok',
            'server_id' => $supplier->id,
            'supplier_id' => $supplier->supplier_id,
        ];
    }

    protected function pushSale(User $user, array $item, string $uuid): array
    {
        $payload = $item['payload'];
        $items = $payload['items'] ?? [];
        $branchId = $this->resolvedBranchId($user, $item);

        if (! is_array($items) || count($items) < 1) {
            throw new \RuntimeException('Sale requires items.');
        }

        if (! empty($payload['customer_id'])) {
            $customerOk = Customer::query()->whereKey($payload['customer_id'])->where('branch_id', $branchId)->exists();
            if (! $customerOk) {
                throw new \RuntimeException('Customer does not belong to this branch.');
            }
        }

        $subtotal = 0;
        $totalDiscount = 0;
        $prepared = [];
        $conversion = app(UnitConversionService::class);

        foreach ($items as $line) {
            $lineTotal = round(((float) ($line['quantity'] ?? 0)) * ((float) ($line['selling_price'] ?? 0)), 2);
            $discount = 0;
            if (! empty($line['discount']) && (float) $line['discount'] > 0) {
                if (($line['discount_type'] ?? '') === 'percentage') {
                    $discount = round($lineTotal * ((float) $line['discount'] / 100), 2);
                } else {
                    $discount = round((float) $line['discount'], 2);
                }
                $lineTotal = round($lineTotal - $discount, 2);
            }
            $totalDiscount += $discount;
            $subtotal += $lineTotal;

            $isCustom = isset($line['is_custom']) && ($line['is_custom'] == '1' || $line['is_custom'] === true);
            $qty = (float) ($line['quantity'] ?? 0);
            $qtyInBase = $qty;
            $product = null;
            if (! $isCustom && ! empty($line['product_id'])) {
                $product = Product::query()->visibleToBranch($branchId)->find($line['product_id']);
                if (! $product) {
                    throw new \RuntimeException('Product not found: '.$line['product_id']);
                }
                $unitId = ! empty($line['unit_id']) ? (int) $line['unit_id'] : null;
                $qtyInBase = $conversion->toBaseQuantity($product, $qty, $unitId);
                if ($qtyInBase > (float) ($product->currentStock($branchId) ?? 0) + 0.000001) {
                    throw new \RuntimeException("Insufficient stock for {$product->name}");
                }
            }

            $prepared[] = [
                'line' => $line,
                'is_custom' => $isCustom,
                'qty' => $qty,
                'qty_in_base' => $qtyInBase,
                'price' => (float) ($line['selling_price'] ?? 0),
                'line_total' => $lineTotal,
                'product' => $product,
            ];
        }

        $totalAmount = round($subtotal, 2);
        $paidAmount = min((float) ($payload['paid_amount'] ?? $totalAmount), $totalAmount);
        $paymentStatus = 'paid';
        if ($paidAmount <= 0) {
            $paymentStatus = 'pending';
        } elseif ($paidAmount < $totalAmount) {
            $paymentStatus = 'partial';
        }

        $customerName = trim((string) ($payload['customer_name'] ?? 'Walk-in Customer')) ?: 'Walk-in Customer';
        $saleNumber = Sale::generateSaleNumber('SALE', $branchId);

        $sale = Sale::create([
            'branch_id' => $branchId,
            'sale_number' => $saleNumber,
            'customer_id' => $payload['customer_id'] ?? null,
            'user_id' => $user->id,
            'sale_date' => now()->toDateString(),
            'subtotal' => $subtotal,
            'tax_amount' => 0,
            'discount_amount' => $totalDiscount,
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'payment_status' => $paymentStatus,
            'status' => 'completed',
            'notes' => $payload['comment'] ?? "Customer: {$customerName}",
        ]);

        foreach ($prepared as $row) {
            $line = $row['line'];
            $isCustom = $row['is_custom'];
            $qty = $row['qty'];
            $price = $row['price'];
            $lineTotal = $row['line_total'];
            $product = $row['product'];

            $productName = $isCustom
                ? ($line['product_name'] ?? 'Custom Product')
                : ($product?->name);

            SaleItem::create([
                'sale_id' => $sale->id,
                'branch_id' => $branchId,
                'product_id' => $isCustom ? null : ($line['product_id'] ?? null),
                'product_name' => $productName,
                'quantity' => $qty,
                'quantity_in_base_unit' => $row['qty_in_base'],
                'unit_id' => $line['unit_id'] ?? null,
                'unit_price' => $price,
                'discount' => $line['discount'] ?? 0,
                'total' => $lineTotal,
            ]);

            if ($product) {
                $product->decrementStock($row['qty_in_base'], $branchId, [
                    'source_type' => 'sale',
                    'source_id' => $sale->id,
                    'reason' => 'offline sync sale',
                    'idempotency_key' => 'sync-sale-'.$uuid.'-'.$product->id,
                ]);
            }
        }

        $this->mapUuid($uuid, 'sale', $sale->id, ['sale_number' => $saleNumber], $branchId);

        return [
            'client_uuid' => $uuid,
            'entity' => 'sale',
            'status' => 'ok',
            'server_id' => $sale->id,
            'sale_number' => $saleNumber,
        ];
    }

    protected function mapUuid(string $uuid, string $entity, $serverId, array $meta = [], ?int $branchId = null): void
    {
        if (! Schema::hasTable('sync_id_mappings')) {
            return;
        }

        $row = [
            'client_uuid' => $uuid,
            'entity' => $entity,
            'server_id' => (string) $serverId,
            'meta' => json_encode($meta),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('sync_id_mappings', 'branch_id')) {
            $row['branch_id'] = $branchId ?? CurrentBranch::id();
        }

        DB::table('sync_id_mappings')->insert($row);
    }

    protected function fullSnapshot(User $user, ?int $branchId): array
    {
        $sales = Sale::query()->orderByDesc('id')->limit(300)->get();
        $saleIds = $sales->pluck('id');

        return [
            'full' => true,
            'server_time' => now()->toIso8601String(),
            'active_branch_id' => $branchId,
            'cache_version' => 2,
            'user' => $this->userPayload($user),
            'products' => $this->productsForBranch($branchId),
            'categories' => Category::query()->orderBy('name')->get(),
            'units' => Unit::query()->orderBy('name')->get(),
            'customers' => Customer::query()->orderBy('name')->limit(2000)->get(),
            'suppliers' => Supplier::query()->orderBy('name')->limit(2000)->get(),
            'sales' => $sales,
            'sale_items' => SaleItem::query()->whereIn('sale_id', $saleIds)->get(),
            'orders' => Order::query()->orderByDesc('id')->limit(300)->get(),
            'expenses' => Expense::query()->orderByDesc('id')->limit(300)->get(),
            'invoices' => Invoice::query()->orderByDesc('id')->limit(300)->get(),
            'branches' => $user->isAdmin()
                ? Branch::query()->orderBy('name')->get()
                : Branch::query()->where('id', $branchId)->get(),
            'product_units' => ProductUnit::query()->get(),
            'unit_conversions' => UnitConversion::query()->get(),
            'branch_stocks' => BranchProductStock::query()
                ->where('branch_id', $branchId)
                ->get()
                ->map(fn ($row) => [
                    'branch_id' => $row->branch_id,
                    'product_id' => $row->product_id,
                    'stock_quantity' => (float) $row->stock_quantity,
                    'quantity' => (float) $row->stock_quantity,
                    'selling_type' => $row->selling_type,
                    'display_name' => $row->display_name,
                    'purchase_price' => $row->purchase_price,
                    'selling_price' => $row->selling_price,
                    'retail_price' => $row->retail_price,
                    'wholesale_price' => $row->wholesale_price,
                    'updated_at' => $row->updated_at,
                ]),
        ];
    }

    protected function productsForBranch(?int $branchId, $sinceAt = null)
    {
        if (! $branchId) {
            return collect();
        }
        $query = Product::query()
            ->visibleToBranch($branchId)
            ->with(['currentBranchStock', 'unit', 'baseUnit', 'productUnits.unit']);
        if ($sinceAt) {
            // Include products whose master row changed OR whose branch overrides/stock changed
            $changedOverrideIds = BranchProductStock::query()
                ->where('branch_id', $branchId)
                ->where('updated_at', '>', $sinceAt)
                ->pluck('product_id');

            $query->where(function ($q) use ($sinceAt, $changedOverrideIds) {
                $q->where('updated_at', '>', $sinceAt);
                if ($changedOverrideIds->isNotEmpty()) {
                    $q->orWhereIn('id', $changedOverrideIds);
                }
            });
        }

        return $query->orderBy('name')->limit(5000)->get()->map(function (Product $product) {
            $branchStock = $product->currentBranchStock;
            $attrs = $product->getAttributes();

            return array_merge($product->toArray(), [
                'owner_branch_id' => $attrs['owner_branch_id'] ?? null,
                'name' => ($branchStock?->display_name !== null && $branchStock->display_name !== '')
                    ? $branchStock->display_name
                    : ($attrs['name'] ?? $product->name),
                'purchase_price' => $branchStock?->purchase_price ?? ($attrs['purchase_price'] ?? null),
                'selling_price' => $branchStock?->selling_price ?? ($attrs['selling_price'] ?? null),
                'retail_price' => $branchStock?->retail_price ?? ($attrs['retail_price'] ?? null),
                'wholesale_price' => $branchStock?->wholesale_price ?? ($attrs['wholesale_price'] ?? null),
                'stock_quantity' => (float) ($branchStock?->stock_quantity ?? 0),
                'selling_type' => $branchStock?->selling_type
                    ?: ($attrs['selling_type'] ?? 'retail'),
                'unit_id' => $attrs['base_unit_id'] ?? $attrs['unit_id'] ?? null,
                'base_unit_id' => $attrs['base_unit_id'] ?? $attrs['unit_id'] ?? null,
                'unit_name' => $product->baseUnit->short_name ?? $product->unit->short_name ?? 'Pcs',
                'selling_units' => $product->sellingUnitsForPos(),
            ]);
        });
    }

    protected function snapshotBranchId(User $user): ?int
    {
        $branchId = CurrentBranch::id($user);
        if ($branchId) {
            return $branchId;
        }

        // Admin with no switcher selection still needs a Phandu catalog for offline POS.
        if ($user->isAdmin()) {
            return CurrentBranch::DEFAULT_BRANCH_ID;
        }

        return null;
    }

    protected function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'branch_id' => $user->branch_id,
            'is_admin' => $user->isAdmin(),
            'active_branch_id' => CurrentBranch::id($user),
        ];
    }
}
