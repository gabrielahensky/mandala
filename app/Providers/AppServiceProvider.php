<?php

namespace App\Providers;
use App\Services\RentBillingService;
use Carbon\Carbon;

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
        view()->composer('*', function ($view) {
            $billing = app(RentBillingService::class);
    
            $view->with(
                'unpaidCount',
                $billing
                    ->unpaidRentsForMonth(Carbon::now())
                    ->count()
            );
        });
    }
}
