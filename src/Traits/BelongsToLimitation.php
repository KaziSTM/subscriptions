<?php

declare(strict_types=1);

namespace KaziSTM\Subscriptions\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToLimitation
{
    public function limitation(): BelongsTo
    {
        return $this->belongsTo(config('subscriptions.models.limitation'), 'limitation_id', 'id', 'limitation');
    }

    public function scopeByLimitationId(Builder $builder, int $limitationId): Builder
    {
        return $builder->where('limitation_id', $limitationId);
    }
}
