<?php

namespace KaziSTM\Subscriptions\Models;

use Illuminate\Database\Eloquent\Model;
use KaziSTM\Subscriptions\Traits\HasSlug;
use KaziSTM\Subscriptions\Traits\HasTranslations;

/**
* @property string $slug
* @property array $title
* @property array $description*
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *

 * @method static \Illuminate\Database\Eloquent\Builder|Feature whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Feature whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Feature whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Feature whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Feature whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Feature whereSlug($value)
 **/


class Limitation extends Model implements Sortable
{

    use HasFactory;
    use HasSlug;
    use HasTranslations;
    use SoftDeletes;
    use SortableTrait;

    protected  $fillable = [
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