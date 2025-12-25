<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Collection;

class Transaction extends Model
{
    /* ======================================================
        CORE
    ======================================================= */

    protected $fillable = [
        'type',
        'category',
        'amount',
        'note',
        'transacted_at',
        'correction_of',
        'source_type',
        'source_id',
    ];

    protected $casts = [
        'transacted_at' => 'datetime',
    ];

    /* ======================================================
        CORRECTION CHAIN
    ======================================================= */

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
        return $this->correction_of !== null;
    }

    /* ======================================================
        TAGGING (DOMAIN CONTEXT)
    ======================================================= */

    public function tags(): HasMany
    {
        return $this->hasMany(TransactionTag::class);
    }

    /* ======================================================
        DERIVED ACCESSORS (READ-ONLY, SAFE)
    ======================================================= */

    public function units(): Collection
    {
        if (! $this->relationLoaded('tags')) {
            $this->load('tags');
        }

        return Unit::whereIn(
            'id',
            $this->tags
                ->where('type', 'unit')
                ->pluck('reference_id')
        )->get();
    }

    public function tenants(): Collection
    {
        if (! $this->relationLoaded('tags')) {
            $this->load('tags');
        }

        return Tenant::whereIn(
            'id',
            $this->tags
                ->where('type', 'tenant')
                ->pluck('reference_id')
        )->get();
    }

    public function hasUnit(): bool
    {
        if (! $this->relationLoaded('tags')) {
            $this->load('tags');
        }

        return $this->tags->contains('type', 'unit');
    }

    public function hasTenant(): bool
    {
        if (! $this->relationLoaded('tags')) {
            $this->load('tags');
        }

        return $this->tags->contains('type', 'tenant');
    }

    /* ======================================================
        QUERY SCOPES — LEDGER CORE
    ======================================================= */

    /**
     * Idempotent source guard
     * contoh: rent_billing:123
     */
    public function scopeFromSource(
        Builder $query,
        string $type,
        int $id
    ): Builder {
        return $query
            ->where('source_type', $type)
            ->where('source_id', $id);
    }

    /**
     * Generic tag filter
     * contoh: tagged('unit', 1)
     */
    public function scopeTagged(
        Builder $query,
        string $type,
        int $id
    ): Builder {
        return $query->whereHas('tags', function ($q) use ($type, $id) {
            $q->where('type', $type)
              ->where('reference_id', $id);
        });
    }

    /* ======================================================
        BACKWARD COMPAT SCOPES (JANGAN DIHAPUS)
    ======================================================= */

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $this->scopeTagged($query, 'tenant', $tenantId);
    }

    public function scopeForUnit(Builder $query, int $unitId): Builder
    {
        return $this->scopeTagged($query, 'unit', $unitId);
    }
}
