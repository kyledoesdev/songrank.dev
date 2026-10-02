<?php

namespace App\Filament\Resources\Reviews\Tables;

use App\Models\Review;
use Carbon\Carbon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ReviewTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(static::columns())
            ->filters([]);
    }

    /**
     * @return array<int, TextColumn|IconColumn>
     */
    public static function columns(): array
    {
        return [
            TextColumn::make('id')
                ->label('ID')
                ->searchable()
                ->sortable(),
            TextColumn::make('user.name')
                ->label('Creator')
                ->searchable()
                ->sortable(),
            TextColumn::make('name')
                ->searchable()
                ->sortable(),
            TextColumn::make('type')
                ->label('Type')
                ->badge()
                ->state(fn (Review $record): string => $record->type->label())
                ->color(fn (Review $record): string => $record->type->filamentColor())
                ->toggleable(isToggledHiddenByDefault: false),
            TextColumn::make('subject')
                ->label('Subject')
                ->state(fn (Review $record): ?string => $record->subject?->name())
                ->toggleable(isToggledHiddenByDefault: false),
            TextColumn::make('stars')
                ->label('Score')
                ->sortable()
                ->formatStateUsing(fn (Review $record): string => "{$record->score()}/10")
                ->placeholder('Not scored'),
            TextColumn::make('comments_count')
                ->sortable()
                ->label('Comments')
                ->counts('comments'),
            IconColumn::make('is_published')
                ->label('Published')
                ->boolean()
                ->sortable()
                ->trueIcon('heroicon-o-check-badge')
                ->falseIcon('heroicon-o-x-mark')
                ->toggleable(isToggledHiddenByDefault: false),
            IconColumn::make('is_public')
                ->label('Public')
                ->boolean()
                ->sortable()
                ->trueIcon('heroicon-o-check-badge')
                ->falseIcon('heroicon-o-x-mark')
                ->toggleable(isToggledHiddenByDefault: false),
            TextColumn::make('created_at')
                ->label('Created')
                ->sortable()
                ->dateTime('M j, Y g:i A', Auth::user()->timezone)
                ->toggleable(isToggledHiddenByDefault: false),
            static::publishedAtColumn(),
        ];
    }

    public static function publishedAtColumn(): TextColumn
    {
        return TextColumn::make('published_at')
            ->label('Published')
            ->sortable()
            ->formatStateUsing(function (Review $record): string {
                $timestamp = $record->getAttributes()['published_at'];

                if (is_null($timestamp)) {
                    return 'Draft';
                }

                return Carbon::parse($timestamp)
                    ->tz(Auth::user()->timezone)
                    ->format('M j, Y g:i A');
            })
            ->toggleable(isToggledHiddenByDefault: false);
    }
}
