<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Customer;
use Illuminate\Http\Request;
use Carbon\Carbon;

class InvoiceReportController extends Controller
{
    public function index(Request $request)
    {
        // Get filter parameters
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $customerId = $request->input('customer_id', 'all');
        $status = $request->input('status', 'all');
        $perPage = $request->input('per_page', 10);

        // Build query for invoices
        $invoicesQuery = Invoice::with('customer', 'sale')
            ->whereBetween('invoice_date', [$startDate, $endDate]);

        // Apply customer filter
        if ($customerId !== 'all') {
            $invoicesQuery->where('customer_id', $customerId);
        }

        // Apply status filter
        if ($status !== 'all') {
            if ($status === 'overdue') {
                $invoicesQuery->where('due_date', '<', Carbon::now()->format('Y-m-d'))
                    ->where('status', '!=', 'paid')
                    ->whereRaw('total_amount > paid_amount');
            } else {
                $invoicesQuery->where('status', $status);
            }
        }

        // Calculate summary metrics using the same balance logic as customer detail.
        $summaryInvoices = (clone $invoicesQuery)->get();
        Invoice::attachCalculatedBalances($summaryInvoices);

        $totalAmount = $summaryInvoices->sum(fn ($invoice) => (float) ($invoice->total_amount ?? 0));
        $totalPaid = $summaryInvoices->sum(
            fn ($invoice) => (float) ($invoice->calculated_paid_amount ?? ($invoice->paid_amount ?? 0))
        );
        $totalUnpaid = $summaryInvoices->sum(
            fn ($invoice) => (float) ($invoice->remaining_balance_due ?? max(0, ($invoice->total_amount ?? 0) - ($invoice->paid_amount ?? 0)))
        );
        
        // Calculate overdue amount (invoices with due_date < today and status != paid)
        $overdueAmount = $summaryInvoices
            ->filter(function ($invoice) {
                return $invoice->due_date
                    && Carbon::parse($invoice->due_date)->lt(Carbon::today())
                    && ($invoice->status !== 'paid');
            })
            ->sum(fn ($invoice) => (float) ($invoice->remaining_balance_due ?? 0));

        // Get paginated invoices
        $invoices = $invoicesQuery
            ->orderBy('invoice_date', 'desc')
            ->paginate($perPage)
            ->appends($request->except('page'));
        Invoice::attachCalculatedBalances($invoices->getCollection());

        // Get all customers for filter dropdown
        $customers = Customer::where('is_active', true)->orderBy('name')->get();

        return view('reports.invoice-report', compact(
            'totalAmount',
            'totalPaid',
            'totalUnpaid',
            'overdueAmount',
            'invoices',
            'customers',
            'startDate',
            'endDate',
            'customerId',
            'status'
        ));
    }
}
