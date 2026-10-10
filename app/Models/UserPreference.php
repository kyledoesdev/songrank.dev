<?php

namespace App\Models;

use App\Enums\CatalogSort;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserPreference extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'recieve_newsletter_emails',
        'enabled_comment_mentions',
        'catalog_sort',
        'catalog_is_public',
    ];

    protected $attributes = [
        'catalog_sort' => 'alphabetical',
        'catalog_is_public' => false,
    ];

    protected function casts(): array
    {
        return [
            'recieve_newsletter_emails' => 'boolean',
            'enabled_comment_mentions' => 'boolean',
            'catalog_sort' => CatalogSort::class,
            'catalog_is_public' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
