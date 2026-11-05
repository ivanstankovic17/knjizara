<?php

namespace App\Models;

use App\Events\BookAvailabilityChanged;
use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

#[Fillable([
    'category_id', 'title', 'slug', 'isbn', 'description',
    'price', 'stock', 'published_year',
])]
class Book extends Model
{
    /** @use HasFactory<BookFactory> */
    use HasFactory;

    protected $attributes = [
        'stock' => 0,
    ];

    /**
     * Nova knjiga bez sluga dobija ga iz naslova, sa četiri cifre na
     * kraju, jer više knjiga može imati isti naslov.
     */
    protected static function booted(): void
    {
        static::creating(function (Book $book) {
            while ($book->slug === null
            || static::where('slug', $book->slug)->exists()) {
                $book->slug = Str::slug($book->title).'-'.random_int(1000, 9999);
            }
        });

        // Broj knjiga po kategoriji je u kešu (odeljak 10.3).
        static::created(fn () => Cache::forget(Category::CACHE_KEY));
        static::deleted(fn () => Cache::forget(Category::CACHE_KEY));

        static::updated(function (Book $book) {
            if ($book->wasChanged('category_id')) {
                Cache::forget(Category::CACHE_KEY);
            }

            // Knjiga je rasprodata ili je ponovo na stanju (odeljak 10.6).
            if ($book->wasChanged('stock')
                && ($book->getOriginal('stock') > 0) !== ($book->stock > 0)) {
                BookAvailabilityChanged::dispatch($book);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'stock' => 'integer',
            'published_year' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class)
            ->withPivot('position')
            ->orderByPivot('position');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    #[Scope]
    protected function inStock(Builder $query): void
    {
        $query->where('stock', '>', 0);
    }
}
