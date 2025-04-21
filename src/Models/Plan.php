<?php

declare(strict_types=1);

namespace KaziSTM\Subscriptions\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use KaziSTM\Subscriptions\Traits\HasLimitations;
use KaziSTM\Subscriptions\Traits\HasSlug;
use KaziSTM\Subscriptions\Traits\HasTranslations;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Spatie\Sluggable\SlugOptions;

/**
 * @property-read int|string $id
 * @property string $slug
 * @property array $name
 * @property array $description
 * @property bool $is_active
 * @property float $price
 * @property float $signup_fee
 * @property string $currency
 * @property int $trial_period
 * @property string $trial_interval
 * @property int $invoice_period
 * @property string $invoice_interval
 * @property int $grace_period
 * @property string $grace_interval
 * @property int $prorate_day
 * @property int $prorate_period
 * @property int $prorate_extend_due
 * @property int $active_subscribers_limit
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection|Feature[] $features
 * @property-read Collection|Subscription[] $subscriptions
 *
 * @method static Builder|Plan ordered($direction = 'asc')
 * @method static Builder|Plan whereActiveSubscribersLimit($value)
 * @method static Builder|Plan whereCreatedAt($value)
 * @method static Builder|Plan whereCurrency($value)
 * @method static Builder|Plan whereDeletedAt($value)
 * @method static Builder|Plan whereDescription($value)
 * @method static Builder|Plan whereGraceInterval($value)
 * @method static Builder|Plan whereGracePeriod($value)
 * @method static Builder|Plan whereId($value)
 * @method static Builder|Plan whereInvoiceInterval($value)
 * @method static Builder|Plan whereInvoicePeriod($value)
 * @method static Builder|Plan whereIsActive($value)
 * @method static Builder|Plan whereName($value)
 * @method static Builder|Plan wherePrice($value)
 * @method static Builder|Plan whereProrateDay($value)
 * @method static Builder|Plan whereProrateExtendDue($value)
 * @method static Builder|Plan whereProratePeriod($value)
 * @method static Builder|Plan whereSignupFee($value)
 * @method static Builder|Plan whereSlug($value)
 * @method static Builder|Plan whereSortOrder($value)
 * @method static Builder|Plan whereTrialInterval($value)
 * @method static Builder|Plan whereTrialPeriod($value)
 * @method static Builder|Plan whereUpdatedAt($value)
 */
class Plan extends Model implements Sortable
{
    use HasFactory;
    use HasLimitations;
    use HasSlug;
    use HasTranslations;
    use SoftDeletes;
    use SortableTrait;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'is_active',
        'price',
        'signup_fee',
        'currency',
        'trial_period',
        'trial_interval',
        'invoice_period',
        'invoice_interval',
        'grace_period',
        'grace_interval',
        'prorate_day',
        'prorate_period',
        'prorate_extend_due',
        'active_subscribers_limit',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'float',
        'signup_fee' => 'float',
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
        return config('subscriptions.tables.plans');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::deleted(function ($plan): void {
            $plan->features()->delete();
            $plan->subscriptions()->delete();
        });
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->doNotGenerateSlugsOnUpdate()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->allowDuplicateSlugs();
    }

    public function features(): HasMany
    {
        return $this->hasMany(config('subscriptions.models.feature'));
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(config('subscriptions.models.subscription'));
    }

    /**
     * Get the limitations associated with this plan through its features.
     * A plan has limitations via the features defined for it.
     */
    public function limitations(): HasManyThrough
    {
        // Assumes Limitation model is KaziSTM\Subscriptions\Models\Limitation
        // Assumes Feature model is KaziSTM\Subscriptions\Models\Feature
        return $this->hasManyThrough(
            config('subscriptions.models.limitation'), // Target model (Limitation)
            config('subscriptions.models.feature'),    // Intermediate model (Feature)
            'plan_id',                                 // Foreign key on intermediate table (features.plan_id)
            'id',                                      // Foreign key on target table (limitations.id)
            'id',                                      // Local key on starting table (plans.id)
            'limitation_id'                          // Local key on intermediate table (features.limitation_id)
        );
    }

    public function isFree(): bool
    {
        return $this->price <= 0.00;
    }

    public function hasTrial(): bool
    {
        return $this->trial_period && $this->trial_interval;
    }

    public function hasGrace(): bool
    {
        return $this->grace_period && $this->grace_interval;
    }

    public function getFeatureBySlug(string $limitationSlug): ?Feature
    {
        return $this->features()
            ->whereHas('limitation', fn ($limitation) => $limitation->where('slug', $limitationSlug))
            ->first();
    }

    public function activate(): self
    {
        $this->update(['is_active' => true]);

        return $this;
    }

    public function deactivate(): self
    {
        $this->update(['is_active' => false]);

        return $this;
    }
}
