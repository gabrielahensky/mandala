<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Unit;
use App\Services\RentBillingService;
use Carbon\Carbon;

class UnitDetail extends Component
{
    public Unit $unit;

    public function mount(Unit $unit)
    {
        $this->unit = $unit->load([
            'activeRent.tenant',
            'rentCycles.tenant',
        ]);
    }

    public function getActiveRentProperty()
    {
        return $this->unit->activeRent;
    }

    public function getBillingSummaryProperty()
    {
        if (!$this->activeRent) {
            return null;
        }

        return app(RentBillingService::class)
            ->summaryForMonth($this->activeRent, now());
    }

    public function getRentTransactionsProperty()
    {
        return $this->unit->transactions()
            ->where('category', 'Rent')
            ->orderByDesc('transacted_at')
            ->get();
    }

    public function render()
    {
        return view('livewire.unit-detail')
            ->layout('layouts.dashboard');
    }
}
