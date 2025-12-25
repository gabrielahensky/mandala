<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Events\RentBillingPaid;
use App\Listeners\CreateLedgerFromRentBilling;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        RentBillingPaid::class => [
            CreateLedgerFromRentBilling::class,
        ],
    ];
}
