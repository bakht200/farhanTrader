<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\CustomerBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::with(['sales' => function($q) {
            $q->latest();
        }]);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('customer_id', 'like', "%{$search}%");
            });
        }

        if ($request->filled('customer_type')) {
            if ($request->customer_type === '__none__') {
                $query->where(function ($q) {
                    $q->whereNull('customer_type')->orWhere('customer_type', '');
                });
            } else {
                $query->where('customer_type', $request->customer_type);
            }
        }

        $customers = $query->latest()
            ->paginate($request->get('per_page', 10))
            ->appends($request->query());

        // Calculate totals and get latest sale/order for each customer
        foreach ($customers as $customer) {
            $balanceSummary = $this->calculateCustomerBalanceSummary($customer->id);
            $customer->total_price = $balanceSummary['total_price'];
            $customer->paid_amount = $balanceSummary['paid_amount'];
            $customer->unpaid_amount = $balanceSummary['unpaid_amount'];
            $customer->latest_order = $customer->sales()->latest()->first();
        }

        // Add Walk-in Customer entry (aggregate all sales with null customer_id)
        // Exclude ADJ bills from balance calculation
        $walkInSalesQuery = Sale::whereNull('customer_id')->where('sale_number', 'not like', 'ADJ-%');
        
        // Apply search filter to walk-in customer if searching
        if ($request->filled('search')) {
            $search = $request->search;
            $walkInSalesQuery->where('sale_number', 'like', "%{$search}%");
        }
        
        $walkInTotalPrice = $walkInSalesQuery->sum('total_amount') ?? 0;
        $walkInPaidAmount = $walkInSalesQuery->sum('paid_amount') ?? 0;
        $walkInUnpaidAmount = $walkInTotalPrice - $walkInPaidAmount;
        $walkInLatestOrder = $walkInSalesQuery->latest()->first();
        
        $hasCustomerTypeFilter = $request->filled('customer_type');
        $showWalkInRow = ! $hasCustomerTypeFilter
            || $request->customer_type === '__none__';

        // Only add Walk-in Customer if there are sales or if searching for it
        // Add it only on the first page to avoid pagination issues (hidden when filtering by a specific type)
        if ($showWalkInRow
            && ($walkInTotalPrice > 0 || ($request->filled('search') && stripos($request->search, 'walk') !== false)) 
            && ($customers->currentPage() == 1 || $request->filled('search'))) {
            $walkInCustomer = (object) [
                'id' => null,
                'customer_id' => 'WALK-IN',
                'name' => 'Walk-in Customer',
                'total_price' => $walkInTotalPrice,
                'paid_amount' => $walkInPaidAmount,
                'unpaid_amount' => $walkInUnpaidAmount,
                'latest_order' => $walkInLatestOrder,
                'is_walk_in' => true
            ];
            
            // Add to the beginning of the collection
            $customers->getCollection()->prepend($walkInCustomer);
        }

        // Calculate totals across ALL customers (not just paginated)
        $allCustomersQuery = Customer::query();
        if ($request->filled('search')) {
            $search = $request->search;
            $allCustomersQuery->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('customer_id', 'like', "%{$search}%");
            });
        }
        if ($request->filled('customer_type')) {
            if ($request->customer_type === '__none__') {
                $allCustomersQuery->where(function ($q) {
                    $q->whereNull('customer_type')->orWhere('customer_type', '');
                });
            } else {
                $allCustomersQuery->where('customer_type', $request->customer_type);
            }
        }
        $allCustomers = $allCustomersQuery->get();
        
        $grandTotalPrice = 0;
        $grandPaidAmount = 0;
        $grandRemaining = 0;
        
        foreach ($allCustomers as $customer) {
            $balanceSummary = $this->calculateCustomerBalanceSummary($customer->id);
            
            $grandTotalPrice += $balanceSummary['total_price'];
            $grandPaidAmount += $balanceSummary['paid_amount'];
            $grandRemaining += $balanceSummary['unpaid_amount'];
        }
        
        // Add Walk-in Customer totals to grand totals (only when not filtering by a specific named type)
        if ($showWalkInRow) {
            $walkInGrandQuery = Sale::whereNull('customer_id')->where('sale_number', 'not like', 'ADJ-%');
            if ($request->filled('search')) {
                $search = $request->search;
                $walkInGrandQuery->where('sale_number', 'like', "%{$search}%");
            }
            $walkInGrandTotal = $walkInGrandQuery->sum('total_amount') ?? 0;
            $walkInGrandPaid = $walkInGrandQuery->sum('paid_amount') ?? 0;
            $walkInGrandRemaining = $walkInGrandTotal - $walkInGrandPaid;

            $grandTotalPrice += $walkInGrandTotal;
            $grandPaidAmount += $walkInGrandPaid;
            $grandRemaining += $walkInGrandRemaining;
        }

        $customerTypesForFilter = Customer::query()
            ->whereNotNull('customer_type')
            ->where('customer_type', '!=', '')
            ->distinct()
            ->orderBy('customer_type')
            ->pluck('customer_type')
            ->values();

        return view('customers.index', compact('customers', 'grandTotalPrice', 'grandPaidAmount', 'grandRemaining', 'customerTypesForFilter'));
    }

    public function create()
    {
        return view('customers.create', [
            'customerTypeOptions' => $this->customerTypeSelectOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|string|unique:customers,customer_id',
            'name' => 'required|string|max:255',
            'customer_type' => 'nullable|string|max:100',
            'email' => 'nullable|email|unique:customers,email',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'country' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'credit_limit' => 'nullable|numeric|min:0',
        ]);

        $customer = Customer::create($validated);
        
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Customer created successfully.',
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'customer_id' => $customer->customer_id,
                    'customer_type' => $customer->customer_type,
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                ]
            ]);
        }
        
        return redirect()->route('customers.index')->with('success', 'Customer created successfully.');
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', [
            'customer' => $customer,
            'customerTypeOptions' => $this->customerTypeSelectOptions($customer->customer_type),
        ]);
    }

    public function show(Customer $customer)
    {
        $balanceSummary = $this->calculateCustomerBalanceSummary($customer->id);
        $customer->total_price = $balanceSummary['total_price'];
        $customer->paid_amount = $balanceSummary['paid_amount'];
        $customer->unpaid_amount = $balanceSummary['unpaid_amount'];

        $customerBills = Sale::where('customer_id', $customer->id)
            ->where('sale_number', 'not like', 'ADJ-%')
            ->orderBy('sale_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($customerBills as $bill) {
            $paid = (float) ($bill->paid_amount ?? 0);
            $total = (float) ($bill->total_amount ?? 0);
            $bill->setAttribute('bill_paid_amount', $paid);
            $bill->setAttribute('bill_remaining', round(max(0, $total - $paid), 2));
        }

        $ledgerEntries = $this->buildCustomerLedger($customer, $customerBills);

        return view('customers.show', compact('customer', 'customerBills', 'ledgerEntries'));
    }

    /**
     * Build a chronological debit/credit ledger for the customer detail page.
     * Credit = bill (sale) amounts owed; Debit = payments received.
     * An "Opening Balance" row absorbs any historical amounts that predate
     * payment logging so the final balance always matches the wallet remaining.
     *
     * @param \Illuminate\Support\Collection<int, Sale> $customerBills
     * @return array{rows: list<array<string, mixed>>, total_debit: float, total_credit: float, final_balance: float}
     */
    protected function buildCustomerLedger(Customer $customer, $customerBills): array
    {
        $entries = [];

        $paymentLogs = \App\Models\CustomerPaymentLog::where('customer_id', $customer->id)
            ->whereIn('log_type', ['payment', 'cash_received'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        // Map sale_id / sale_number -> first non-empty POS comment for that sale
        $commentsBySaleId = [];
        $commentsBySaleNumber = [];
        foreach ($paymentLogs as $log) {
            $comment = trim((string) ($log->comment ?? ''));
            if ($comment === '') {
                continue;
            }
            if ($log->sale_id && ! isset($commentsBySaleId[$log->sale_id])) {
                $commentsBySaleId[$log->sale_id] = $comment;
            }
            $ref = trim((string) ($log->reference_number ?? ''));
            if ($ref !== '' && ! isset($commentsBySaleNumber[$ref])) {
                $commentsBySaleNumber[$ref] = $comment;
            }
        }

        foreach ($customerBills as $sale) {
            $narration = $commentsBySaleId[$sale->id]
                ?? $commentsBySaleNumber[$sale->sale_number]
                ?? 'Sale';

            // Prefer created_at so same-day payments appear in real checkout order
            // (sale_date is date-only and would sort every bill before that day's payments).
            $saleAt = $sale->created_at ?? $sale->sale_date;

            $entries[] = [
                'timestamp' => $saleAt->timestamp,
                'tiebreak' => $sale->id,
                'date' => $saleAt,
                'type' => 'Sale',
                'ref' => $sale->sale_number,
                'narration' => $narration,
                'debit' => null,
                'credit' => (float) $sale->total_amount,
            ];
        }

        // POS/Sale payment may split one typed amount into:
        // (1) a direct "Cash/Payment received for Sale: X" log, and
        // (2) one+ "Previous balance payment from Sale: X" logs applied to older bills.
        // Merge only logs from the same checkout (same origin sale, within a few seconds)
        // so later payments on the same sale stay separate.
        $saleRefPattern = '([A-Z]+-\d+)';
        $mergeWindowSeconds = 5;

        $directsBySale = [];
        foreach ($paymentLogs as $log) {
            $description = (string) ($log->description ?? '');
            $ref = trim((string) ($log->reference_number ?? ''));
            $directSale = null;

            if (preg_match('/(?:Cash|Payment) received for Sale:\s*' . $saleRefPattern . '/i', $description, $m)) {
                $directSale = strtoupper($m[1]);
            } elseif (
                in_array($log->log_type, ['cash_received', 'payment'], true)
                && $ref !== ''
                && preg_match('/^' . $saleRefPattern . '$/i', $ref)
                && ! preg_match('/Previous balance payment from Sale:/i', $description)
                && ! preg_match('/Applied PKR .+ to Sale:/i', $description)
            ) {
                $directSale = strtoupper($ref);
            }

            if ($directSale) {
                $directsBySale[$directSale][] = $log;
            }
        }

        $groupKeyByLogId = [];
        foreach ($paymentLogs as $log) {
            $description = (string) ($log->description ?? '');
            if (! preg_match('/Previous balance payment from Sale:\s*' . $saleRefPattern . '/i', $description, $m)) {
                continue;
            }

            $originSale = strtoupper($m[1]);
            $bestDirect = null;
            $bestDiff = PHP_INT_MAX;

            foreach ($directsBySale[$originSale] ?? [] as $directLog) {
                $diff = abs($log->created_at->getTimestamp() - $directLog->created_at->getTimestamp());
                if ($diff <= $mergeWindowSeconds && $diff < $bestDiff) {
                    $bestDirect = $directLog;
                    $bestDiff = $diff;
                }
            }

            if ($bestDirect) {
                $groupKey = 'pos:' . $bestDirect->id;
                $groupKeyByLogId[$log->id] = $groupKey;
                $groupKeyByLogId[$bestDirect->id] = $groupKey;
            } else {
                // Surplus without a matching direct payment (rare) — keep same-checkout
                // surplus rows together by origin + rounded time bucket.
                $bucket = (int) floor($log->created_at->timestamp / $mergeWindowSeconds);
                $groupKeyByLogId[$log->id] = 'pos-surplus:' . $originSale . '|' . $bucket;
            }
        }

        $paymentGroups = [];
        foreach ($paymentLogs as $log) {
            $description = (string) ($log->description ?? '');
            $ref = trim((string) ($log->reference_number ?? ''));
            $groupKey = $groupKeyByLogId[$log->id] ?? ('log:' . $log->id);

            $displayRef = $ref !== '' ? $ref : '-';
            if (str_starts_with($groupKey, 'pos:') || str_starts_with($groupKey, 'pos-surplus:')) {
                if (preg_match('/Previous balance payment from Sale:\s*' . $saleRefPattern . '/i', $description, $m)
                    || preg_match('/(?:Cash|Payment) received for Sale:\s*' . $saleRefPattern . '/i', $description, $m)
                ) {
                    $displayRef = strtoupper($m[1]);
                } elseif (preg_match('/^' . $saleRefPattern . '$/i', $ref)) {
                    $displayRef = strtoupper($ref);
                }
            }

            if (! isset($paymentGroups[$groupKey])) {
                $paymentGroups[$groupKey] = [
                    'amount' => 0.0,
                    'created_at' => $log->created_at,
                    'ref' => $displayRef,
                    'comment' => trim((string) ($log->comment ?? '')),
                ];
            }

            $paymentGroups[$groupKey]['amount'] += (float) $log->amount;

            if ($log->created_at->lt($paymentGroups[$groupKey]['created_at'])) {
                $paymentGroups[$groupKey]['created_at'] = $log->created_at;
            }

            // Prefer the originating sale number as the visible ref for merged rows
            if (
                (str_starts_with($groupKey, 'pos:') || str_starts_with($groupKey, 'pos-surplus:'))
                && preg_match('/(?:Cash|Payment) received for Sale:\s*' . $saleRefPattern . '/i', $description, $m)
            ) {
                $paymentGroups[$groupKey]['ref'] = strtoupper($m[1]);
            } elseif (
                str_starts_with($groupKey, 'pos-surplus:')
                && preg_match('/Previous balance payment from Sale:\s*' . $saleRefPattern . '/i', $description, $m)
            ) {
                $paymentGroups[$groupKey]['ref'] = strtoupper($m[1]);
            }

            $comment = trim((string) ($log->comment ?? ''));
            if ($comment !== '' && $paymentGroups[$groupKey]['comment'] === '') {
                $paymentGroups[$groupKey]['comment'] = $comment;
            }
        }

        foreach ($paymentGroups as $group) {
            $comment = $group['comment'];
            $entries[] = [
                'timestamp' => $group['created_at']->timestamp,
                'tiebreak' => PHP_INT_MAX, // payments on the same second appear after that second's bills
                'date' => $group['created_at'],
                'type' => 'Payment',
                'ref' => $group['ref'] ?? '-',
                'narration' => $comment !== '' ? $comment : 'Payment received',
                'debit' => round($group['amount'], 2),
                'credit' => null,
            ];
        }

        usort($entries, function ($a, $b) {
            return [$a['timestamp'], $a['tiebreak']] <=> [$b['timestamp'], $b['tiebreak']];
        });

        $totalCredit = array_sum(array_map(fn ($e) => $e['credit'] ?? 0, $entries));
        $totalDebit = array_sum(array_map(fn ($e) => $e['debit'] ?? 0, $entries));

        // Payments recorded before logging existed leave a gap between
        // (credits - debits) and the real remaining balance; absorb it up front.
        $opening = round(((float) $customer->unpaid_amount) - ($totalCredit - $totalDebit), 2);
        if (abs($opening) >= 0.01) {
            $openingEntry = [
                'timestamp' => 0,
                'tiebreak' => 0,
                'date' => null,
                'type' => 'Opening',
                'ref' => '-',
                'narration' => 'Opening balance',
                'debit' => $opening < 0 ? abs($opening) : null,
                'credit' => $opening > 0 ? $opening : null,
            ];
            array_unshift($entries, $openingEntry);
            if ($opening > 0) {
                $totalCredit += $opening;
            } else {
                $totalDebit += abs($opening);
            }
        }

        $running = 0.0;
        foreach ($entries as &$entry) {
            $running += ($entry['credit'] ?? 0) - ($entry['debit'] ?? 0);
            $entry['balance'] = round($running, 2);
        }
        unset($entry);

        return [
            'rows' => $entries,
            'total_debit' => round($totalDebit, 2),
            'total_credit' => round($totalCredit, 2),
            'final_balance' => round($running, 2),
        ];
    }

    public function dayWiseBills(Request $request, Customer $customer)
    {
        try {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            
            if (!$startDate || !$endDate) {
                return response()->json([
                    'error' => 'Start date and end date are required'
                ], 400);
            }

            // Get all sales for this customer between the specified dates (excluding ADJ bills)
            $sales = Sale::where('customer_id', $customer->id)
                ->where('sale_number', 'not like', 'ADJ-%')
                ->whereDate('sale_date', '>=', $startDate)
                ->whereDate('sale_date', '<=', $endDate)
                ->with(['items.product.unit'])
                ->orderBy('sale_date')
                ->orderBy('created_at')
                ->get();

            // Build ADJ lookup once, then merge ADJ paid amounts with related sales.
            $adjBills = Sale::where('customer_id', $customer->id)
                ->where('sale_number', 'like', 'ADJ-%')
                ->get(['sale_number', 'notes', 'paid_amount']);

            $adjPaidByParentSaleNumber = [];
            $adjBillNumberByParentSaleNumber = [];
            foreach ($adjBills as $adjBill) {
                if ($adjBill->notes && preg_match('/Sale:\s*(\S+)/', $adjBill->notes, $m)) {
                    $parentSaleNumber = $m[1];
                    $adjPaidByParentSaleNumber[$parentSaleNumber] = ($adjPaidByParentSaleNumber[$parentSaleNumber] ?? 0)
                        + (float) ($adjBill->paid_amount ?? 0);
                    $adjBillNumberByParentSaleNumber[$parentSaleNumber] = $adjBill->sale_number;
                }
            }

            // Merge ADJ bills with their related Sales
            foreach ($sales as $sale) {
                $adjPaidAmount = (float) ($adjPaidByParentSaleNumber[$sale->sale_number] ?? 0);

                // If ADJ paid amount exists, merge it into this sale's paid_amount.
                if ($adjPaidAmount > 0) {
                    $sale->paid_amount = ($sale->paid_amount ?? 0) + $adjPaidAmount;
                    $sale->adj_bill_number = $adjBillNumberByParentSaleNumber[$sale->sale_number] ?? null;
                    $sale->adj_paid_amount = $adjPaidAmount;
                }
            }

            // Calculate previous balance for each sale
            $salesData = [];
            foreach ($sales as $sale) {
                // Calculate previous balance up to this sale (excluding ADJ bills)
                $previousSales = Sale::where('customer_id', $customer->id)
                    ->where('sale_number', 'not like', 'ADJ-%')
                    ->where(function($q) use ($sale) {
                        $q->where('sale_date', '<', $sale->sale_date)
                          ->orWhere(function($subQ) use ($sale) {
                              $subQ->where('sale_date', '=', $sale->sale_date)
                                   ->where('id', '<', $sale->id);
                          });
                    })
                    ->get();
                
                // Calculate previous balance accounting for ADJ payments
                // ADJ payments reduce previous balance, so we need to include them
                $previousTotal = $previousSales->sum('total_amount');
                $previousPaid = 0;
                $previousAdjPaid = 0;
                
                foreach ($previousSales as $prevSale) {
                    // Get regular paid amount
                    $previousPaid += ($prevSale->paid_amount ?? 0);
                    
                    // Find and add ADJ payment for this previous sale
                    $previousAdjPaid += (float) ($adjPaidByParentSaleNumber[$prevSale->sale_number] ?? 0);
                }
                
                // Previous balance = previous totals - (previous regular payments + previous ADJ payments)
                $previousBalance = max(0, $previousTotal - ($previousPaid + $previousAdjPaid));

                // Get merged ADJ payment amount if exists
                $previousBalancePayment = $sale->adj_paid_amount ?? 0;

                // Calculate regular paid amount (excluding ADJ)
                $regularPaidAmount = ($sale->paid_amount ?? 0) - ($sale->adj_paid_amount ?? 0);
                
                $salesData[] = [
                    'id' => $sale->id,
                    'sale_number' => $sale->sale_number,
                    'sale_date' => $sale->created_at ? $sale->created_at->format('Y-m-d h:i A') : ($sale->sale_date ? $sale->sale_date->format('Y-m-d') : null),
                    'total_amount' => $sale->total_amount ?? 0,
                    'paid_amount' => $sale->paid_amount ?? 0,
                    'regular_paid_amount' => $regularPaidAmount,
                    'payment_status' => $sale->payment_status ?? 'pending',
                    'previous_balance' => round($previousBalance, 2),
                    'previous_balance_payment' => round($previousBalancePayment, 2),
                    'adj_bill_number' => $sale->adj_bill_number ?? null,
                    'adj_paid_amount' => $sale->adj_paid_amount ?? 0,
                    'items' => $sale->items->map(function($item) {
                        return [
                            'id' => $item->id,
                            'product_id' => $item->product_id,
                            'product_name' => $item->product_name ?? ($item->product->name ?? 'N/A'),
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'discount' => $item->discount ?? 0,
                            'tax' => $item->tax ?? 0,
                            'total' => $item->total ?? 0,
                            'unit_name' => $item->product && $item->product->unit ? $item->product->unit->short_name : 'Pcs',
                            'unit_short_name' => $item->product && $item->product->unit ? $item->product->unit->short_name : 'Pcs',
                        ];
                    })
                ];
            }

            return response()->json([
                'success' => true,
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'customer_id' => $customer->customer_id ?? 'CN-' . str_pad($customer->id, 3, '0', STR_PAD_LEFT),
                    'phone' => $customer->phone ?? '',
                ],
                'sales' => $salesData,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error fetching bills: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|string|unique:customers,customer_id,' . $customer->id,
            'name' => 'required|string|max:255',
            'customer_type' => 'nullable|string|max:100',
            'email' => 'nullable|email|unique:customers,email,' . $customer->id,
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'country' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'credit_limit' => 'nullable|numeric|min:0',
        ]);

        $customer->update($validated);
        return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    protected function customerTypeSelectOptions(?string $includeValue = null): \Illuminate\Support\Collection
    {
        $merged = Customer::query()
            ->whereNotNull('customer_type')
            ->where('customer_type', '!=', '')
            ->distinct()
            ->orderBy('customer_type')
            ->pluck('customer_type')
            ->values();

        if ($includeValue !== null && $includeValue !== '' && ! $merged->contains($includeValue)) {
            return $merged->push($includeValue)->unique()->sort()->values();
        }

        return $merged;
    }

    /**
     * Keep customer-level balances consistent with the detail page:
     * - exclude ADJ invoices from total sales
     * - include ADJ paid amounts as payments against parent sales
     * - cap paid at cumulative total to avoid negative outstanding values
     */
    protected function calculateCustomerBalanceSummary(int $customerId): array
    {
        return app(CustomerBalanceService::class)->calculateCustomerBalanceSummary($customerId);
    }

    /**
     * Parse POS/Sale payment log lines so ADJ "toward older balance" can list target bills.
     *
     * @return array<string, list<array{to: string, amount: float}>>
     */
    protected function adjAllocationsGroupedBySourceSale(int $customerId): array
    {
        $byParent = [];
        $logs = \App\Models\CustomerPaymentLog::query()
            ->where('customer_id', $customerId)
            ->where('description', 'like', '%Previous balance payment from Sale:%')
            ->orderBy('id')
            ->get(['description']);

        foreach ($logs as $log) {
            $d = (string) ($log->description ?? '');
            if (!preg_match('/Previous balance payment from Sale:\s*(\S+)\.\s*Applied PKR\s*([\d,\.]+)\s*to Sale:\s*(\S+)/', $d, $m)) {
                continue;
            }
            $parent = $m[1];
            $amount = (float) str_replace(',', '', $m[2]);
            $target = $m[3];
            $byParent[$parent][] = ['to' => $target, 'amount' => $amount];
        }

        return $byParent;
    }

    public function addPreviousBalance(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        try {
            DB::beginTransaction();

            // Create a Sale with "previous balance" as the product name
            $sale = Sale::create([
                'sale_number' => Sale::generateSaleNumber('PB'),
                'customer_id' => $customer->id,
                'user_id' => auth()->id(),
                'sale_date' => now(),
                'subtotal' => $validated['amount'],
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => $validated['amount'],
                'paid_amount' => 0,
                'payment_status' => 'pending',
                'status' => 'completed',
                'notes' => 'Previous Balance',
            ]);

            // Create a SaleItem with "previous balance" as product name
            SaleItem::create([
                'sale_id' => $sale->id,
                'product_id' => null,
                'product_name' => 'Previous Balance',
                'quantity' => 1,
                'unit_price' => $validated['amount'],
                'discount' => 0,
                'tax' => 0,
                'total' => $validated['amount'],
            ]);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Previous balance added successfully.',
                    'sale_number' => $sale->sale_number
                ]);
            }

            return redirect()->route('customers.index')->with('success', 'Previous balance added successfully. Sale Number: ' . $sale->sale_number);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error adding previous balance: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Error adding previous balance.');
        }
    }

    public function printAllCustomersReport(Request $request)
    {
        try {
            // Optional date range filters from request
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');

            // Normalize simple date strings; if invalid, treat as null
            if ($fromDate && !strtotime($fromDate)) {
                $fromDate = null;
            }
            if ($toDate && !strtotime($toDate)) {
                $toDate = null;
            }

            // Base customers query (we'll query sales per customer with the date range)
            $customers = Customer::all();

            // If no explicit dates provided, determine overall date range from all sales
            if (!$fromDate) {
                $fromDate = Sale::whereNotNull('sale_date')->min('sale_date');
            }
            if (!$toDate) {
                $toDate = Sale::whereNotNull('sale_date')->max('sale_date');
            }

            // Process data for each customer and calculate grand totals
            $customersData = [];
            $grandTotalPrice = 0;
            $grandPaidAmount = 0;
            $grandRemaining = 0;
            
            foreach ($customers as $customer) {
                // Build sales query within optional date range - exclude ADJ bills
                $salesQuery = $customer->sales()->where('sale_number', 'not like', 'ADJ-%');
                if ($fromDate) {
                    $salesQuery->whereDate('sale_date', '>=', $fromDate);
                }
                if ($toDate) {
                    $salesQuery->whereDate('sale_date', '<=', $toDate);
                }

                // Clone for aggregations
                $salesForTotals = clone $salesQuery;

                // Calculate totals within the selected date range (or complete if no dates)
                $totalPrice = $salesForTotals->sum('total_amount') ?? 0;
                
                // Sum paid_amount directly - ADJ payments are already reflected in old sales' paid_amount
                $paidAmount = $salesForTotals->sum('paid_amount') ?? 0;
                
                $remaining = $totalPrice - $paidAmount;
                $totalSales = $salesForTotals->count();
                
                // Add to grand totals
                $grandTotalPrice += $totalPrice;
                $grandPaidAmount += $paidAmount;
                $grandRemaining += $remaining;

                // Detailed sales list (each purchase with date, amounts, status)
                $salesDetails = $salesQuery
                    ->orderBy('sale_date')
                    ->orderBy('created_at')
                    ->get(['sale_number', 'sale_date', 'total_amount', 'paid_amount', 'payment_status', 'status'])
                    ->map(function ($sale) {
                        return [
                            'sale_number' => $sale->sale_number,
                            'sale_date' => optional($sale->sale_date)->toDateString(),
                            'total_amount' => $sale->total_amount,
                            'paid_amount' => $sale->paid_amount,
                            'remaining' => ($sale->total_amount ?? 0) - ($sale->paid_amount ?? 0),
                            'payment_status' => $sale->payment_status,
                            'status' => $sale->status,
                        ];
                    })
                    ->values()
                    ->all();
                
                $customersData[] = [
                    'customer' => [
                        'id' => $customer->id,
                        'customer_id' => $customer->customer_id,
                        'name' => $customer->name ?? '',
                        'email' => $customer->email ?? '',
                        'phone' => $customer->phone ?? '',
                        'address' => $customer->address ?? '',
                        'city' => $customer->city ?? '',
                        'state' => $customer->state ?? '',
                        'country' => $customer->country ?? '',
                    ],
                    'summary' => [
                        'total_price' => $totalPrice,
                        'paid_amount' => $paidAmount,
                        'remaining' => $remaining,
                        'total_sales' => $totalSales,
                    ],
                    'sales' => $salesDetails,
                ];
            }

            // Always return JSON for AJAX requests
            if (request()->expectsJson() || request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'customers' => $customersData,
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                    'grand_totals' => [
                        'total_price' => $grandTotalPrice,
                        'paid_amount' => $grandPaidAmount,
                        'remaining' => $grandRemaining,
                    ],
                ]);
            }

            return view('customers.print-all-report', compact('customersData', 'fromDate', 'toDate'));
        } catch (\Exception $e) {
            if (request()->expectsJson() || request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'error' => $e->getTraceAsString()
                ], 500);
            }
            throw $e;
        }
    }
}