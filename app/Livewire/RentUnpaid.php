<?php

namespace App\Livewire;

use Livewire\Component;
use Carbon\Carbon;
use App\Models\RentCycle;
use App\Services\RentBillingService;
use App\Services\RentPaymentService;
use Illuminate\Validation\ValidationException;

class RentUnpaid extends Component
{
    /* =====================================================
        FILTER
    ====================================================== */
    public string $month;

    /* =====================================================
        DISPLAY DATA
    ====================================================== */
    public array $rows = [];

    /* =====================================================
        PAYMENT MODAL STATE
    ====================================================== */
    public bool $showPaymentModal = false;
    public ?RentCycle $rentContext = null;
    public int $maxPayable = 0;

    /* =====================================================
        PAYMENT FORM
    ====================================================== */
    public int $amount = 0;
    public string $paidAt;
    public string $note = ''; // UI-only, not sent to service

    /* =====================================================
        LIFECYCLE
    ====================================================== */
    public function mount(): void
    {
        $this->month  = now()->format('Y-m');
        $this->paidAt = now()->toDateString();

        $this->reload();
    }

    public function updatedMonth(): void
    {
        $this->reload();
    }

    /* =====================================================
        DATA LOAD
    ====================================================== */
    protected function reload(): void
    {
        $billing = app(RentBillingService::class);

        $this->rows = $billing
            ->unpaidRentsForMonth(
                Carbon::createFromFormat('Y-m', $this->month)
            )
            ->map(fn ($row) => [
                'rent'        => $row['rent'],   // RentCycle
                'unit'        => $row['unit'],
                'tenant'      => $row['tenant'],
                'expected'    => $row['expected'],
                'paid'        => $row['paid'],
                'outstanding' => $row['outstanding'],
            ])
            ->values()
            ->toArray();
    }

    /* =====================================================
        OPEN / CLOSE PAYMENT MODAL
    ====================================================== */
    public function openPaymentModal(int $index): void
    {
        if (! isset($this->rows[$index])) {
            return;
        }

        $row = $this->rows[$index];

        $this->rentContext = $row['rent'];
        $this->maxPayable  = $row['outstanding'];

        $this->amount = $row['outstanding'];
        $this->paidAt = now()->toDateString();
        $this->note   = '';

        $this->showPaymentModal = true;
    }

    public function closePaymentModal(): void
    {
        $this->showPaymentModal = false;
        $this->rentContext = null;
        $this->maxPayable = 0;
    }

    /* =====================================================
        SAVE PAYMENT
    ====================================================== */
    public function savePayment(): void
    {
        if (! $this->rentContext) {
            throw ValidationException::withMessages([
                'payment' => 'Invalid rent context.',
            ]);
        }

        $this->validate([
            'amount' => "required|integer|min:1|max:{$this->maxPayable}",
            'paidAt' => 'required|date',
            'note'   => 'nullable|string|max:255',
        ]);

        app(RentPaymentService::class)->applyPayment(
            rent: $this->rentContext,
            amount: $this->amount,
            paidAt: $this->paidAt
        );

        $this->closePaymentModal();
        $this->reload();
    }

    /* =====================================================
        RENDER
    ====================================================== */
    public function render()
    {
        return view('livewire.rent-unpaid')
            ->layout('layouts.dashboard');
    }
}
