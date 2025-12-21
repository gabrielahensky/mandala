<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionEvidence extends Model
{
    protected $table = 'transaction_evidences';

    public $timestamps = false;

    protected $fillable = [
        'transaction_id',
        'type',
        'path',
        'note',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
