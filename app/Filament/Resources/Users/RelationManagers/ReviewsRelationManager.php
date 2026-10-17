<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Actions\Reviews\DestroyReview;
use App\Filament\Resources\Reviews\ReviewResource;
use App\Filament\Resources\Reviews\Tables\ReviewTable;
use App\Models\Review;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class ReviewsRelationManager extends RelationManager
{
    protected static string $relationship = 'reviews';

    protected static ?string $title = 'Reviews';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return ReviewTable::configure($table)
            ->recordTitleAttribute('name')
            ->recordActions([
                ViewAction::make()
                    ->url(fn (Review $record): string => ReviewResource::getUrl('view', ['record' => $record])),
                DeleteAction::make()
                    ->using(fn (Review $record) => (new DestroyReview)->handle($record)),
            ]);
    }
}
