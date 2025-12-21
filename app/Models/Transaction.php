<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class Transaction extends Model
{
    protected $fillable = [
        'type',
        'amount',
        'category',
        'note',
        'transacted_at',
        'correction_of',
    ];

    protected $casts = [
        'transacted_at' => 'date',
    ];

    /* =========================
        CORRECTION RELATION
    ========================== */

    public function original(): BelongsTo
    {
        return $this->belongsTo(self::class, 'correction_of');
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(self::class, 'correction_of');
    }

    public function isCorrection(): bool
    {
        return !is_null($this->correction_of);
    }

    /* =========================
        EVIDENCE
    ========================== */

    public function evidences(): HasMany
    {
        return $this->hasMany(TransactionEvidence::class);
    }

    public function hasEvidence(): bool
    {
        return $this->relationLoaded('evidences')
            ? $this->evidences->isNotEmpty()
            : $this->evidences()->exists();
    }

    /* =========================
        TAG CORE RELATION
    ========================== */

    public function tags(): HasMany
    {
        return $this->hasMany(TransactionTag::class);
    }

    /* =========================
        DERIVED DOMAIN ACCESSORS
    ========================== */

    public function units(): Collection
    {
        return Unit::whereIn(
            'id',
            $this->tags
                ->where('type', 'unit')
                ->pluck('reference_id')
        )->get();
    }

    public function tenants(): Collection
    {
        return Tenant::whereIn(
            'id',
            $this->tags
                ->where('type', 'tenant')
                ->pluck('reference_id')
        )->get();
    }

    public function hasUnit(): bool
    {
        return $this->tags->contains('type', 'unit');
    }

    public function hasTenant(): bool
    {
        return $this->tags->contains('type', 'tenant');
    }

    /* =========================
        QUERY SCOPES (LEDGER)
    ========================== */

    public function scopeForUnit(Builder $query, int $unitId): Builder
    {
        return $query->whereHas('tags', function ($q) use ($unitId) {
            $q->where('type', 'unit')
              ->where('reference_id', $unitId);
        });
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->whereHas('tags', function ($q) use ($tenantId) {
            $q->where('type', 'tenant')
              ->where('reference_id', $tenantId);
        });
    }
}
