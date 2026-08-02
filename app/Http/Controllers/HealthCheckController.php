<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HealthCheckController extends Controller
{
    public function index()
    {
        $checks = [];

        $checks[] = $this->buildCheck(
            'Application',
            true,
            'Laravel ' . app()->version() . ' / PHP ' . PHP_VERSION
        );

        try {
            DB::select('SELECT 1');
            $checks[] = $this->buildCheck('Database', true, 'Connection successful');
        } catch (\Throwable $e) {
            $checks[] = $this->buildCheck('Database', false, $e->getMessage());
        }

        try {
            $cacheKey = 'health_check_ping_' . now()->timestamp;
            Cache::put($cacheKey, 'ok', now()->addMinutes(1));
            $cacheOk = Cache::get($cacheKey) === 'ok';
            Cache::forget($cacheKey);
            $checks[] = $this->buildCheck('Cache', $cacheOk, $cacheOk ? 'Read/write successful' : 'Read/write failed');
        } catch (\Throwable $e) {
            $checks[] = $this->buildCheck('Cache', false, $e->getMessage());
        }

        try {
            $logsWritable = is_writable(storage_path('logs'));
            $frameworkWritable = is_writable(storage_path('framework'));
            $storageOk = $logsWritable && $frameworkWritable;
            $checks[] = $this->buildCheck(
                'Storage',
                $storageOk,
                $storageOk ? 'Writable' : 'Storage path is not writable'
            );
        } catch (\Throwable $e) {
            $checks[] = $this->buildCheck('Storage', false, $e->getMessage());
        }

        $queueDetail = 'Driver: ' . config('queue.default', 'unknown');
        $queueOk = true;
        try {
            if (Schema::hasTable('failed_jobs')) {
                $failedCount = DB::table('failed_jobs')->count();
                $queueDetail .= ' | Failed Jobs: ' . $failedCount;
                if ($failedCount > 0) {
                    $queueOk = false;
                }
            } else {
                $queueDetail .= ' | failed_jobs table not found';
            }
        } catch (\Throwable $e) {
            $queueOk = false;
            $queueDetail .= ' | ' . $e->getMessage();
        }
        $checks[] = $this->buildCheck('Queue', $queueOk, $queueDetail);

        $allHealthy = collect($checks)->every(fn ($check) => $check['ok']);

        return view('health-check.index', [
            'checks' => $checks,
            'allHealthy' => $allHealthy,
            'checkedAt' => now(),
        ]);
    }

    protected function buildCheck(string $name, bool $ok, string $detail): array
    {
        return [
            'name' => $name,
            'ok' => $ok,
            'detail' => $detail,
        ];
    }
}
