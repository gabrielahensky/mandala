<?php

namespace App\Livewire;

use Livewire\Component;
use Carbon\Carbon;
use App\Services\RentBillingService;
use App\Services\RentPaymentService;
use Illuminate\Validation\ValidationException;

class RentUnpaid extends Component
{
    /* =========================
        FILTER STATE
    ========================== */
    public string $month;

    /* =========================
        DATA
    ========================== */
    public array $rows = [];

    /* =========================
        MODAL STATE
    ========================== */
    public bool $showUnitModal = false;
    public array $selectedRow = [];

    /* =========================
        PAYMENT FORM
    ========================== */
    public int $amount = 0;
    public string $paidAt;
    public string $note = '';

    protected RentBillingService $billing;

    /* =========================
        LIFECYCLE
    ========================== */
    public function mount(RentBillingService $billing): void
    {
        $this->billing = $billing;

        $this->month  = now()->format('Y-m');
        $this->paidAt = now()->toDateString();

        $this->load();
    }

    public function updatedMonth(): void
    {
        $this->load();
    }

    /* =========================
        CORE LOAD
    ========================== */
    protected function load(): void
    {
        $this->rows = $this->billing
            ->unpaidRentsForMonth(
                Carbon::createFromFormat('Y-m', $this->month)
            )
            ->values()
            ->toArray();
    }

    /* =========================
        OPEN / CLOSE MODAL
    ========================== */
    public function openUnitModal(int $index): void
    {
        if (! isset($this->rows[$index])) {
            return;
        }

        $this->selectedRow = $this->rows[$index];

        // default payment = outstanding
        $this->amount = $this->selectedRow['outstanding'];
        $this->paidAt = now()->toDateString();
        $this->note   = '';

        $this->showUnitModal = true;
    }

    public function closeUnitModal(): void
    {
        $this->showUnitModal = false;
        $this->selectedRow = [];
    }

    /* =========================
        SAVE PAYMENT
    ========================== */
    public function savePayment(): void
    {
        if (! isset($this->selectedRow['rent_cycle'])) {
            throw ValidationException::withMessages([
                'payment' => 'Invalid rent context.',
            ]);
        }

        $maxAmount = $this->selectedRow['outstanding'];

        $this->validate([
            'amount' => "required|integer|min:1|max:{$maxAmount}",
            'paidAt' => 'required|date',
            'note'   => 'nullable|string|max:255',
        ]);

        /** @var \App\Services\RentPaymentService $payment */
        $payment = app(\App\Services\RentPaymentService::class);

        $payment->applyPayment(
            rent: $this->selectedRow['rent_cycle'],
            amount: $this->amount,
            paidAt: $this->paidAt,
            note: $this->note
        );

        $this->closeUnitModal();
        $this->load();
    }

    /* =========================
        RENDER
    ========================== */
    public function render()
    {
        return view('livewire.rent-unpaid')
            ->layout('layouts.dashboard');
    }
}
