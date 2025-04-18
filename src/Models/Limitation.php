<?php

declare(strict_types=1);

namespace KaziSTM\Subscriptions\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kazistm\Subscriptions\Traits\HasSlug;
use Kazistm\Subscriptions\Traits\HasTranslations;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Spatie\Sluggable\SlugOptions;

/**
 * @property string $slug
 * @property array $title
 * @property array $description*
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *

 * @method static Builder|Feature whereCreatedAt($value)
 * @method static Builder|Feature whereDeletedAt($value)
 * @method static Builder|Feature whereDescription($value)
 * @method static Builder|Feature whereId($value)
 * @method static Builder|Feature whereTitle($value)
 * @method static Builder|Feature whereSlug($value)
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
        'name',
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
            ->generateSlugsFrom('name')
            ->allowDuplicateSlugs()
            ->saveSlugsTo('slug');
    }

    public function features(): HasMany
    {
        return $this->hasMany(config('subscriptions.models.feature'));
    }
}
