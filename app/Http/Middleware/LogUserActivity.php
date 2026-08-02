<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogUserActivity
{
    protected $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        // Calculate execution time
        $executionTime = microtime(true) - $startTime;

        // Only log if user is authenticated
        if (auth()->check()) {
            try {
                $this->activityLogService->log(
                    $request,
                    $this->activityLogService->determineActivityType($request),
                    null,
                    null,
                    "User {$request->method()} request to {$request->path()}",
                    null,
                    $response->getStatusCode() >= 400 ? 'error' : 'success',
                    $response->getStatusCode(),
                    $executionTime
                );
            } catch (\Exception $e) {
                // Silently fail to not break the application
                // Log error if needed: \Log::error('Activity logging failed: ' . $e->getMessage());
            }
        }

        return $response;
    }
}
