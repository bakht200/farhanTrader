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

        return $this->aiView('ai-insights.index', compact('topStats', 'healthScore', 'insights', 'reorderAlerts'));
    }

    public function forecast()
    {
        $forecast = $this->aiService->getSalesForecast();
        $trends = $this->aiService->getSalesTrends();

        return $this->aiView('ai-insights.forecast', compact('forecast', 'trends'));
    }

    public function inventory()
    {
        $abcAnalysis = $this->aiService->getAbcAnalysis();
        $reorderAlerts = $this->aiService->getReorderAlerts();

        return $this->aiView('ai-insights.inventory', compact('abcAnalysis', 'reorderAlerts'));
    }

    public function customers()
    {
        $rfm = $this->aiService->getCustomerRfmSegmentation();

        return $this->aiView('ai-insights.customers', compact('rfm'));
    }

    public function recommendations()
    {
        $recommendations = $this->aiService->getProductRecommendations();

        return $this->aiView('ai-insights.recommendations', compact('recommendations'));
    }

    public function anomalies()
    {
        $anomalies = $this->aiService->getAnomalyDetection();

        return $this->aiView('ai-insights.anomalies', compact('anomalies'));
    }

    protected function aiView(string $view, array $data)
    {
        return response()
            ->view($view, $data)
            ->header('Cache-Control', 'no-store, private');
    }
}
