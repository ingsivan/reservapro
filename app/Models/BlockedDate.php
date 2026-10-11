<?php

namespace App\Models;

use Database\Factories\BlockedDateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A whole day the business is closed (holiday, vacation...).
 */
#[Fillable(['business_id', 'date', 'reason'])]
class BlockedDate extends Model
{
    /** @use HasFactory<BlockedDateFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
