<?php

declare(strict_types=1);

namespace KaziSTM\Subscriptions\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read int|string $id
 * @property int $used
 * @property Carbon|null $valid_until
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Feature $feature
 * @property-read Subscription $subscription
 *
 * @method static Builder|SubscriptionUsage byFeatureSlug($featureSlug)
 * @method static Builder|SubscriptionUsage whereCreatedAt($value)
 * @method static Builder|SubscriptionUsage whereDeletedAt($value)
 * @method static Builder|SubscriptionUsage whereFeatureId($value)
 * @method static Builder|SubscriptionUsage whereId($value)
 * @method static Builder|SubscriptionUsage whereSubscriptionId($value)
 * @method static Builder|SubscriptionUsage whereUpdatedAt($value)
 * @method static Builder|SubscriptionUsage whereUsed($value)
 * @method static Builder|SubscriptionUsage whereValidUntil($value)
 */
class SubscriptionUsage extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'subscription_id',
        'feature_id',
        'used',
        'valid_until',
    ];

    protected $casts = [
        'used' => 'integer',
        'valid_until' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function getTable(): string
    {
        return config('subscriptions.tables.subscription_usage');
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(config('subscriptions.models.feature'), 'feature_id', 'id', 'feature');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(config('subscriptions.models.subscription'), 'subscription_id', 'id', 'subscription');
    }

    public function scopeByFeatureSlug(Builder $builder, string $featureSlug, int $planId): Builder
    {
        $model = config('subscriptions.models.feature', Feature::class);
        $feature = $model::where('plan_id', $planId)->where('slug', $featureSlug)->first();

        return $builder->where('feature_id', $feature ? $feature->getKey() : null);
    }

    public function expired(): bool
    {
        if (! $this->valid_until) {
            return false;
        }

        return Carbon::now()->gte($this->valid_until);
    }
}
