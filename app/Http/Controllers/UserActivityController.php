<?php

namespace App\Http\Controllers;

use App\Models\UserActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserActivityController extends Controller
{
    public function index(Request $request)
    {
        $query = UserActivityLog::with('user');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('page', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('process', 'like', "%{$search}%")
                  ->orWhere('button', 'like', "%{$search}%")
                  ->orWhere('url', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('browser', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // User filter
        if ($request->filled('user_id') && $request->user_id !== 'all') {
            $query->where('user_id', $request->user_id);
        }

        // Activity type filter
        if ($request->filled('activity_type') && $request->activity_type !== 'all') {
            $query->where('activity_type', $request->activity_type);
        }

        // Status filter
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // IP address filter
        if ($request->filled('ip_address')) {
            $query->where('ip_address', 'like', "%{$request->ip_address}%");
        }

        // Browser filter
        if ($request->filled('browser') && $request->browser !== 'all') {
            $query->where('browser', $request->browser);
        }

        // Device type filter
        if ($request->filled('device_type') && $request->device_type !== 'all') {
            $query->where('device_type', $request->device_type);
        }

        // Platform filter
        if ($request->filled('platform') && $request->platform !== 'all') {
            $query->where('platform', $request->platform);
        }

        // Method filter
        if ($request->filled('method') && $request->method !== 'all') {
            $query->where('method', $request->method);
        }

        // Date range filter
        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        // Time range filter
        if ($request->filled('start_time')) {
            $query->whereTime('time', '>=', $request->start_time);
        }

        if ($request->filled('end_time')) {
            $query->whereTime('time', '<=', $request->end_time);
        }

        // Route name filter
        if ($request->filled('route_name')) {
            $query->where('route_name', 'like', "%{$request->route_name}%");
        }

        // Page filter (renamed to page_filter to avoid conflict with pagination)
        if ($request->filled('page_filter')) {
            $query->where('page', 'like', "%{$request->page_filter}%");
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Handle date and time sorting
        if ($sortBy === 'date') {
            $query->orderBy('date', $sortOrder)->orderBy('time', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $activities = $query->paginate($request->get('per_page', 25))
            ->appends($request->query());

        // Get filter options
        $users = User::orderBy('name')->get();
        $activityTypes = UserActivityLog::whereNotNull('activity_type')
            ->distinct()
            ->pluck('activity_type')
            ->filter()
            ->sort()
            ->values();
        
        $browsers = UserActivityLog::whereNotNull('browser')
            ->distinct()
            ->pluck('browser')
            ->filter()
            ->sort()
            ->values();
        
        $deviceTypes = UserActivityLog::whereNotNull('device_type')
            ->distinct()
            ->pluck('device_type')
            ->filter()
            ->sort()
            ->values();
        
        $platforms = UserActivityLog::whereNotNull('platform')
            ->distinct()
            ->pluck('platform')
            ->filter()
            ->sort()
            ->values();
        
        $methods = UserActivityLog::whereNotNull('method')
            ->distinct()
            ->pluck('method')
            ->filter()
            ->sort()
            ->values();
        
        $statuses = UserActivityLog::whereNotNull('status')
            ->distinct()
            ->pluck('status')
            ->filter()
            ->sort()
            ->values();

        return view('user-activities.index', compact(
            'activities',
            'users',
            'activityTypes',
            'browsers',
            'deviceTypes',
            'platforms',
            'methods',
            'statuses'
        ));
    }

    public function show(UserActivityLog $userActivity)
    {
        $userActivity->load('user');
        return view('user-activities.show', compact('userActivity'));
    }
}
