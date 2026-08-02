<?php

namespace App\Http\Controllers;

use App\Services\AiInsightsService;
use Illuminate\Http\Request;

class AiInsightsController extends Controller
{
    public function __construct(
        protected AiInsightsService $aiService
    ) {}

    public function index()
    {
        $topStats = $this->aiService->getTopSummaryStats();
        $healthScore = $this->aiService->getBusinessHealthScore();
        $insights = $this->aiService->getSmartInsights();
        $reorderAlerts = $this->aiService->getReorderAlerts();

        return view('ai-insights.index', compact('topStats', 'healthScore', 'insights', 'reorderAlerts'));
    }

    public function forecast()
    {
        $forecast = $this->aiService->getSalesForecast();
        $trends = $this->aiService->getSalesTrends();

        return view('ai-insights.forecast', compact('forecast', 'trends'));
    }

    public function inventory()
    {
        $abcAnalysis = $this->aiService->getAbcAnalysis();
        $reorderAlerts = $this->aiService->getReorderAlerts();

        return view('ai-insights.inventory', compact('abcAnalysis', 'reorderAlerts'));
    }

    public function customers()
    {
        $rfm = $this->aiService->getCustomerRfmSegmentation();

        return view('ai-insights.customers', compact('rfm'));
    }

    public function recommendations()
    {
        $recommendations = $this->aiService->getProductRecommendations();

        return view('ai-insights.recommendations', compact('recommendations'));
    }

    public function anomalies()
    {
        $anomalies = $this->aiService->getAnomalyDetection();

        return view('ai-insights.anomalies', compact('anomalies'));
    }
}
