<?php

declare(strict_types=1);

namespace KaziSTM\Subscriptions\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use KaziSTM\Subscriptions\Services\Period;
use KaziSTM\Subscriptions\Traits\BelongsToLimitation;
use KaziSTM\Subscriptions\Traits\BelongsToPlan;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

/**
 * @property-read int|string $id
 * @property string $value
 * @property int $resettable_period
 * @property string $resettable_interval
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Plan $plan
 * @property-read Limitation $limitation
 * @property-read Collection|SubscriptionUsage[] $usages
 *
 * @method static Builder|Feature byPlanId($planId)
 * @method static Builder|Feature ordered($direction = 'asc')
 * @method static Builder|Feature whereCreatedAt($value)
 * @method static Builder|Feature whereDeletedAt($value)
 * @method static Builder|Feature whereDescription($value)
 * @method static Builder|Feature whereId($value)
 * @method static Builder|Feature wherePlanId($value)
 * @method static Builder|Feature whereResettableInterval($value)
 * @method static Builder|Feature whereResettablePeriod($value)
 * @method static Builder|Feature whereSortOrder($value)
 * @method static Builder|Feature whereUpdatedAt($value)
 * @method static Builder|Feature whereValue($value)
 */
class Feature extends Model implements Sortable
{
    use BelongsToLimitation;
    use BelongsToPlan;
    use HasFactory;
    use SoftDeletes;
    use SortableTrait;

    protected $fillable = [
        'plan_id',
        'limitation_id',
        'value',
        'resettable_period',
        'resettable_interval',
        'sort_order',
    ];

    protected $casts = [
        'slug' => 'string',
        'value' => 'string',
        'resettable_period' => 'integer',
        'resettable_interval' => 'string',
        'sort_order' => 'integer',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that are translatable.
     */
    public array $translatable = [
        'name',
        'description',
    ];

    public array $sortable = [
        'order_column_name' => 'sort_order',
    ];

    public function getTable(): string
    {
        return config('subscriptions.tables.features', 'features');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::deleted(function (Feature $feature): void {
            $feature->usage()->delete();
        });
    }

    public function usages(): HasMany
    {
        return $this->hasMany(config('subscriptions.models.subscription_usage'));
    }

    public function getResetDate(?Carbon $dateFrom = null): Carbon
    {
        $period = new Period($this->resettable_interval, $this->resettable_period, $dateFrom ?? Carbon::now());

        return $period->getEndDate();
    }
}
