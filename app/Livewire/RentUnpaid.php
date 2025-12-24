<?php

namespace App\Livewire;

use Livewire\Component;
use Carbon\Carbon;
use App\Services\RentBillingService;

class RentUnpaid extends Component
{
    public string $month;
    public array $rows = [];

    protected RentBillingService $billing;

    public function mount(RentBillingService $billing)
    {
        $this->billing = $billing;
        $this->month   = now()->format('Y-m');

        $this->load();
    }

    public function updatedMonth()
    {
        $this->load();
    }

    protected function load(): void
    {
        $this->rows = $this->billing
            ->unpaidRentsForMonth(
                Carbon::createFromFormat('Y-m', $this->month)
            )
            ->toArray();
    }

    public function render()
    {
        return view('livewire.rent-unpaid')
            ->layout('layouts.dashboard');
    }
}
