<?php

namespace App\Services;

use App\Models\UserActivityLog;
use App\Support\CurrentBranch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{

    /**
     * Log user activity
     *
     * @param Request $request
     * @param string $activityType
     * @param string|null $button
     * @param string|null $process
     * @param string|null $description
     * @param array|null $requestData
     * @param string|null $status
     * @param int|null $responseCode
     * @param float|null $executionTime
     * @return UserActivityLog
     */
    public function log(
        Request $request,
        string $activityType,
        ?string $button = null,
        ?string $process = null,
        ?string $description = null,
        ?array $requestData = null,
        ?string $status = 'success',
        ?int $responseCode = null,
        ?float $executionTime = null
    ): UserActivityLog {
        // Get user agent information
        $userAgent = $request->userAgent();
        
        // Parse browser and platform from user agent
        $browserInfo = $this->parseBrowser($userAgent);
        $platformInfo = $this->parsePlatform($userAgent);
        
        // Determine device type
        $deviceType = $this->detectDeviceType($userAgent);

        // Sanitize request data (remove sensitive information)
        $sanitizedData = $this->sanitizeRequestData($requestData ?? $request->all());

        // Get page name from route or URL
        $page = $this->getPageName($request);

        // Prepare log data
        $logData = [
            'user_id' => Auth::id(),
            'branch_id' => CurrentBranch::id(),
            'ip_address' => $request->ip(),
            'browser' => $browserInfo['name'] ?? null,
            'browser_version' => $browserInfo['version'] ?? null,
            'platform' => $platformInfo,
            'device_type' => $deviceType,
            'user_agent' => $userAgent,
            'url' => $request->fullUrl(),
            'route_name' => $request->route() ? $request->route()->getName() : null,
            'method' => $request->method(),
            'page' => $page,
            'activity_type' => $activityType,
            'button' => $button,
            'process' => $process,
            'description' => $description,
            'request_data' => $sanitizedData,
            'status' => $status,
            'response_code' => $responseCode ?? 200,
            'execution_time' => $executionTime,
            'date' => now()->toDateString(),
            'time' => now()->toTimeString(),
            'session_id' => $request->session()->getId(),
            'referrer' => $request->header('referer'),
        ];

        return UserActivityLog::create($logData);
    }

    /**
     * Parse browser information from user agent
     *
     * @param string $userAgent
     * @return array
     */
    protected function parseBrowser(string $userAgent): array
    {
        $browser = 'Unknown';
        $version = null;

        if (preg_match('/MSIE|Trident/i', $userAgent)) {
            $browser = 'Internet Explorer';
            if (preg_match('/MSIE\s+(\d+)/i', $userAgent, $matches)) {
                $version = $matches[1];
            }
        } elseif (preg_match('/Edge/i', $userAgent)) {
            $browser = 'Edge';
            if (preg_match('/Edge\/(\d+)/i', $userAgent, $matches)) {
                $version = $matches[1];
            }
        } elseif (preg_match('/Chrome/i', $userAgent)) {
            $browser = 'Chrome';
            if (preg_match('/Chrome\/(\d+)/i', $userAgent, $matches)) {
                $version = $matches[1];
            }
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $browser = 'Firefox';
            if (preg_match('/Firefox\/(\d+)/i', $userAgent, $matches)) {
                $version = $matches[1];
            }
        } elseif (preg_match('/Safari/i', $userAgent) && !preg_match('/Chrome/i', $userAgent)) {
            $browser = 'Safari';
            if (preg_match('/Version\/(\d+)/i', $userAgent, $matches)) {
                $version = $matches[1];
            }
        } elseif (preg_match('/Opera|OPR/i', $userAgent)) {
            $browser = 'Opera';
            if (preg_match('/(?:Opera|OPR)\/(\d+)/i', $userAgent, $matches)) {
                $version = $matches[1];
            }
        }

        return ['name' => $browser, 'version' => $version];
    }

    /**
     * Parse platform information from user agent
     *
     * @param string $userAgent
     * @return string
     */
    protected function parsePlatform(string $userAgent): string
    {
        if (preg_match('/Windows NT (\d+\.\d+)/i', $userAgent, $matches)) {
            $version = $matches[1];
            if ($version == '10.0') return 'Windows 10';
            if ($version == '6.3') return 'Windows 8.1';
            if ($version == '6.2') return 'Windows 8';
            if ($version == '6.1') return 'Windows 7';
            return 'Windows';
        } elseif (preg_match('/Mac OS X (\d+[._]\d+)/i', $userAgent, $matches)) {
            return 'macOS';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            return 'Linux';
        } elseif (preg_match('/Android/i', $userAgent)) {
            return 'Android';
        } elseif (preg_match('/iPhone|iPad|iPod/i', $userAgent)) {
            return 'iOS';
        }

        return 'Unknown';
    }

    /**
     * Detect device type from user agent
     *
     * @param string $userAgent
     * @return string
     */
    protected function detectDeviceType(string $userAgent): string
    {
        if (preg_match('/Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $userAgent)) {
            if (preg_match('/iPad/i', $userAgent)) {
                return 'tablet';
            }
            return 'mobile';
        }
        return 'desktop';
    }

    /**
     * Sanitize request data by removing sensitive information
     *
     * @param array $data
     * @return array
     */
    protected function sanitizeRequestData(array $data): array
    {
        $sensitiveKeys = ['password', 'password_confirmation', 'token', 'api_key', 'secret', 'credit_card', 'cvv'];
        
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                $sanitized[$key] = '***REDACTED***';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeRequestData($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Get page name from route or URL
     *
     * @param Request $request
     * @return string
     */
    protected function getPageName(Request $request): string
    {
        $route = $request->route();
        
        if ($route && $route->getName()) {
            $routeName = $route->getName();
            // Convert route name to readable page name
            $pageName = str_replace(['.', '-', '_'], ' ', $routeName);
            return ucwords($pageName);
        }

        // Fallback to URL path
        $path = $request->path();
        $path = str_replace(['/', '-', '_'], ' ', $path);
        return ucwords($path) ?: 'Unknown Page';
    }

    /**
     * Log a simple activity (for middleware usage)
     *
     * @param Request $request
     * @return void
     */
    public function logRequest(Request $request): void
    {
        // Skip logging for certain routes or methods
        if ($this->shouldSkipLogging($request)) {
            return;
        }

        $activityType = $this->determineActivityType($request);
        
        $this->log(
            $request,
            $activityType,
            null,
            null,
            "User {$activityType} {$request->method()} request to {$request->path()}"
        );
    }

    /**
     * Determine if logging should be skipped
     *
     * @param Request $request
     * @return bool
     */
    protected function shouldSkipLogging(Request $request): bool
    {
        // Skip AJAX requests that are not important
        if ($request->ajax() && !$request->is('*api*')) {
            return true;
        }

        // Skip certain routes
        $skipRoutes = ['livewire.*', 'horizon.*', 'telescope.*'];
        foreach ($skipRoutes as $pattern) {
            if ($request->routeIs($pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine activity type from request
     *
     * @param Request $request
     * @return string
     */
    public function determineActivityType(Request $request): string
    {
        $method = $request->method();
        $route = $request->route()?->getName() ?? '';

        // Map HTTP methods to activity types
        $methodMap = [
            'GET' => 'view',
            'POST' => 'create',
            'PUT' => 'update',
            'PATCH' => 'update',
            'DELETE' => 'delete',
        ];

        $baseType = $methodMap[$method] ?? 'action';

        // Check for specific activity types in route name
        if (str_contains($route, 'login')) {
            return 'login';
        }
        if (str_contains($route, 'logout')) {
            return 'logout';
        }
        if (str_contains($route, 'export') || str_contains($route, 'print')) {
            return 'export';
        }
        if (str_contains($route, 'search')) {
            return 'search';
        }

        return $baseType;
    }
}
