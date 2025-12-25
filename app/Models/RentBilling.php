<?php

namespace App\Models;

use App\Events\RentBillingPaid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class RentBilling extends Model
{
    /* ======================================================
        CONFIG
    ======================================================= */

    protected $fillable = [
        'rent_cycle_id',
        'billing_month',
        'amount',
        'due_date',
        'paid_at',
        'status',
    ];

    protected $casts = [
        'billing_month' => 'date',
        'due_date'      => 'date',
        'paid_at'       => 'datetime',
    ];

    /* ======================================================
        RELATIONS
    ======================================================= */

    public function rentCycle()
    {
        return $this->belongsTo(RentCycle::class);
    }

    /* ======================================================
        DOMAIN STATE
    ======================================================= */

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    /* ======================================================
        DOMAIN ACTIONS (IDEMPOTENT)
    ======================================================= */

    public function markPaid(Carbon|string|null $paidAt = null): void
    {
        if ($this->isPaid()) {
            return; // 🔐 aman dipanggil berkali-kali
        }

        $this->update([
            'paid_at' => $paidAt ? Carbon::parse($paidAt) : now(),
        ]);

        event(new RentBillingPaid($this));
    }

    /* ======================================================
        REMINDER LOGIC
    ======================================================= */

    public function daysLeft(): int
    {
        return now()
            ->startOfDay()
            ->diffInDays($this->due_date, false);
    }

    public function reminderLabel(): ?string
    {
        if ($this->isPaid()) {
            return null;
        }

        $days = $this->daysLeft();

        if ($days < 0) {
            return 'OVERDUE';
        }

        if ($days === 0) {
            return 'Today';
        }

        if ($days <= 7) {
            return 'H-' . $days;
        }

        return null;
    }
}
