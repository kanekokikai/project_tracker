<?php

namespace App\Providers;

use App\Models\Project;
use Illuminate\Support\Facades\URL;
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
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        View::composer('layouts.app', function ($view) {
            $authenticated = session('app_authenticated') === true && session('auth_member_id');
            $topics = collect();

            if (request()->routeIs('projects.show') && $authenticated) {
                try {
                    $topics = Project::query()
                        ->where('status', '進行中')
                        ->orderBy('name')
                        ->get(['id', 'name', 'status']);
                } catch (\Throwable) {
                    $topics = collect();
                }
            }

            $view->with('isAuthenticated', $authenticated);
            $view->with('currentMemberName', $authenticated ? (string) session('auth_member_name', '') : '');
            $view->with('sidebarTopics', $topics);
        });
    }
}
