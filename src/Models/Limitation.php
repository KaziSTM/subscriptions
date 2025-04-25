<?php

declare(strict_types=1);

namespace KaziSTM\Subscriptions\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use KaziSTM\Subscriptions\Traits\HasSlug;
use KaziSTM\Subscriptions\Traits\HasTranslations;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Spatie\Sluggable\SlugOptions;

/**
 * @property string $slug
 * @property array $title
 * @property array $description
 * @property string $type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *

 * @method static Builder|Limitation whereCreatedAt($value)
 * @method static Builder|Limitation whereDeletedAt($value)
 * @method static Builder|Limitation whereDescription($value)
 * @method static Builder|Limitation whereId($value)
 * @method static Builder|Limitation whereTitle($value)
 * @method static Builder|Limitation whereSlug($value)
 * @method static Builder|Limitation whereType($value)
 **/
class Limitation extends Model implements Sortable
{
    use HasFactory;
    use HasSlug;
    use HasTranslations;
    use SoftDeletes;
    use SortableTrait;

    protected $fillable = [
        'slug',
        'title',
        'description',
        'type',
        'sort_order',
    ];

    protected $casts = [
        'slug' => 'string',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array
     */
    public $translatable = [
        'title',
        'description',
    ];

    public array $sortable = [
        'order_column_name' => 'sort_order',
    ];

    public function getTable(): string
    {
        return config('subscriptions.tables.limitations', 'limitations');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::deleted(function ($limitation): void {
            $limitation->features()->delete();
        });
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->doNotGenerateSlugsOnUpdate()
            ->generateSlugsFrom('title')
            ->allowDuplicateSlugs()
            ->saveSlugsTo('slug');
    }

    public function features(): HasMany
    {
        return $this->hasMany(config('subscriptions.models.feature'));
    }
}
