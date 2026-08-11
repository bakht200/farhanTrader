<?php

namespace App\Http\Controllers;

use App\Services\ProfitReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfitLossReportController extends Controller
{
    public function __construct(
        protected ProfitReportService $profitReportService
    ) {}

    public function index(Request $request): View
    {
        $mode = $request->input('mode', 'daily');

        $filters = [
            'start_date' => $request->input('start_date', now()->toDateString()),
            'end_date' => $request->input('end_date', now()->toDateString()),
            'start_month' => $request->input('start_month', now()->format('Y-m')),
            'end_month' => $request->input('end_month', now()->format('Y-m')),
            'start_year' => $request->input('start_year', now()->year),
            'end_year' => $request->input('end_year', now()->year),
        ];

        $range = $this->profitReportService->resolveRange($mode, $filters);
        $summary = $this->profitReportService->summarize($range['start'], $range['end']);
        $bills = $this->profitReportService->bills($range['start'], $range['end']);

        $years = range((int) now()->year, (int) now()->year - 10);

        return view('reports.profit-loss', [
            'mode' => in_array($mode, ['daily', 'monthly', 'yearly'], true) ? $mode : 'daily',
            'filters' => $filters,
            'range' => $range,
            'summary' => $summary,
            'bills' => $bills,
            'years' => $years,
        ]);
    }
}
