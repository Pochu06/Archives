<?php

namespace App\Providers;

use App\Models\Research;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

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
        URL::forceScheme('https');

        View::composer(['layouts.app', 'dashboards.admin'], function ($view): void {
            $role = session('user_role');
            $collegeId = session('user_college_id');

            $collegeApprovalCount = 0;
            $rdeApprovalCount = 0;

            if ($role === 'admin' && $collegeId) {
                $collegeApprovalCount = Research::where('college_id', $collegeId)
                    ->where('status', Research::STATUS_PENDING_COLLEGE)
                    ->count();
            }

            if ($role === 'super_admin' || ($role === 'admin' && ! $collegeId)) {
                $rdeApprovalCount = Research::where('status', Research::STATUS_PENDING_RDE)->count();
            }

            $view->with([
                'collegeApprovalCount' => $collegeApprovalCount,
                'rdeApprovalCount' => $rdeApprovalCount,
            ]);
        });
    }
}