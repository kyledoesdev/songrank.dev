<?php

namespace App\Models;

use App\Enums\LegalDocumentType;
use App\QueryBuilders\LegalDocumentQueryBuilder;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UseEloquentBuilder(LegalDocumentQueryBuilder::class)]
class LegalDocument extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'type',
        'content',
        'effective_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => LegalDocumentType::class,
            'effective_at' => 'datetime',
        ];
    }
}
