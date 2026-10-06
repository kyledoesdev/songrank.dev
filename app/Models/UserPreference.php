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
    ];

    protected $attributes = [
        'catalog_sort' => 'alphabetical',
    ];

    protected function casts(): array
    {
        return [
            'recieve_newsletter_emails' => 'boolean',
            'enabled_comment_mentions' => 'boolean',
            'catalog_sort' => CatalogSort::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
