<?php

namespace App\Http\Middleware;

use App\Support\CurrentBranch;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBranchContext
{
    /**
     * @var list<string>
     */
    protected array $writeExceptions = [
        'branches.switch',
        'logout',
        'profile.update',
        'profile.destroy',
        'sync.enroll-vault',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (! $user->isAdmin()) {
            if (! $user->is_active) {
                abort(403, 'This account is disabled.');
            }

            if (! CurrentBranch::id($user)) {
                abort(403, 'Your account is not assigned to an active branch.');
            }

            return $next($request);
        }

        if ($request->isMethodSafe() || in_array($request->route()?->getName(), $this->writeExceptions, true)) {
            return $next($request);
        }

        if (! CurrentBranch::id($user)) {
            abort(403, 'Select a branch before making changes.');
        }

        return $next($request);
    }
}
