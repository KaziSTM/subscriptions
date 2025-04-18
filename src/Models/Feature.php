<?php

declare(strict_types=1);

namespace KaziSTM\Subscriptions\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;
use Kazistm\Subscriptions\Traits\BelongsToLimitation;
use Laravelcm\Subscriptions\Services\Period;
use Laravelcm\Subscriptions\Traits\BelongsToPlan;
use Laravelcm\Subscriptions\Traits\HasSlug;
use Laravelcm\Subscriptions\Traits\HasTranslations;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Spatie\Sluggable\SlugOptions;

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
 * @property-read \Illuminate\Database\Eloquent\Collection|SubscriptionUsage[] $usages
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Feature byPlanId($planId)
 * @method static \Illuminate\Database\Eloquent\Builder|Feature ordered($direction = 'asc')
 * @method static \Illuminate\Database\Eloquent\Builder|Feature whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Feature whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Feature whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Feature whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Feature wherePlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Feature whereResettableInterval($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Feature whereResettablePeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Feature whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Feature whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Feature whereValue($value)
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
     *
     * @var array
     */
    public $translatable = [
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
