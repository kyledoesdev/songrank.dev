<?php

namespace App\Filament\Resources\Rankings\Tables;

use App\Models\Ranking;
use App\QueryBuilders\RankingQueryBuilder;
use Carbon\Carbon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class RankingTable
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
                ->state(fn (Ranking $record): string => $record->type->label())
                ->color(fn (Ranking $record): string => $record->type->filamentColor())
                ->toggleable(isToggledHiddenByDefault: false),
            TextColumn::make('source')
                ->label('Source')
                ->state(fn (Ranking $record): ?string => $record->source?->name())
                ->searchable(query: fn (RankingQueryBuilder $query, string $search) => $query->whereSourceNameLike($search))
                ->toggleable(isToggledHiddenByDefault: false),
            IconColumn::make('is_ranked')
                ->label('Is Ranked')
                ->boolean()
                ->sortable()
                ->trueIcon('heroicon-o-check-badge')
                ->falseIcon('heroicon-o-x-mark')
                ->toggleable(isToggledHiddenByDefault: false),
            IconColumn::make('is_public')
                ->label('Is Public')
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
            static::completedAtColumn(),
            TextColumn::make('songs_count')
                ->sortable()
                ->label('Songs')
                ->counts('songs'),
        ];
    }

    public static function completedAtColumn(): TextColumn
    {
        return TextColumn::make('completed_at')
            ->label('Completed')
            ->sortable()
            ->formatStateUsing(function (Ranking $record): string {
                $timestamp = $record->getAttributes()['completed_at'];

                if (is_null($timestamp)) {
                    return 'In Progress';
                }

                return Carbon::parse($timestamp)
                    ->tz(Auth::user()->timezone)
                    ->format('M j, Y g:i A');
            })
            ->toggleable(isToggledHiddenByDefault: false);
    }
}
