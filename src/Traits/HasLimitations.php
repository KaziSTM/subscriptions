<?php

declare(strict_types=1);

namespace KaziSTM\Subscriptions\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasLimitations
{
    /**
     * Scope query to plans that have a feature associated with a specific Limitation slug.
     */
    public function scopeWhereHasLimitationSlug(Builder $query, string $limitationSlug): Builder
    {
        return $query->whereHas('features.limitation', function (Builder $q) use ($limitationSlug) {
            $q->where('slug', $limitationSlug);
        });
    }

    /**
     * Check if the plan has a specific feature defined via Limitation slug.
     */
    public function hasLimitation(string $limitationSlug): bool
    {
        return $this->limitations()->where('slug', $limitationSlug)->exists();
    }
}
