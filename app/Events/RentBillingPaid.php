<?php

namespace App\Events;

use App\Models\RentBilling;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RentBillingPaid
{
    use Dispatchable, SerializesModels;

    public RentBilling $billing;

    public function __construct(RentBilling $billing)
    {
        $this->billing = $billing;
    }
}
