<?php

namespace App\Providers;

use App\Models\Branch;
use App\Support\CurrentBranch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(['layouts.app', 'components.sidebar'], function ($view) {
            $user = Auth::user();
            $currentBranch = null;
            $currentBranchId = null;
            $branchesForSwitcher = collect();

            if ($user) {
                $currentBranchId = CurrentBranch::id($user);
                $currentBranch = CurrentBranch::get($user);

                if ($user->isAdmin()) {
                    $branchesForSwitcher = Branch::where('is_active', true)->orderBy('id')->get();
                }
            }

            $view->with([
                'currentBranch' => $currentBranch,
                'currentBranchId' => $currentBranchId,
                'branchesForSwitcher' => $branchesForSwitcher,
            ]);
        });
    }
}
