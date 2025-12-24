<?php

namespace App\Models;
use Carbon\Carbon;

use Illuminate\Database\Eloquent\Model;

class RentBilling extends Model
{
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
        'due_date' => 'date',
        'paid_at' => 'date',
    ];

    public function rentCycle()
    {
        return $this->belongsTo(RentCycle::class);
    }

    public function markPaid(Carbon|string|null $paidAt = null): void
    {
        $this->update([
            'status'  => 'paid',
            'paid_at' => $paidAt ? Carbon::parse($paidAt) : now(),
        ]);
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    public function daysLeft(): int
    {
        return now()->startOfDay()->diffInDays(
            $this->due_date,
            false // allow negative
        );
    }

    public function reminderLabel(): ?string
    {
        if ($this->paid_at) {
            return null;
        }

        $days = $this->daysLeft();

        if ($days < 0) {
            return 'OVERDUE';
        }

        if ($days === 0) {
            return 'H';
        }

        if ($days <= 7) {
            return 'H-' . $days;
        }

        return null; // masih jauh, gak perlu ditampilkan
    }
}
