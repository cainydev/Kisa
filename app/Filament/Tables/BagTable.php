<?php

namespace App\Filament\Tables;

use App\Filament\Tables\Columns\BagAmountColumn;
use App\Models\Bag;
use Carbon\Carbon;
use Exception;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BagTable
{
    /**
     * @throws Exception
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->emptyStateHeading('Keine Gebinde gefunden')
            ->query(fn(): Builder => Bag::query())
            ->modifyQueryUsing(function (Builder $query) use ($table): Builder {
                $arguments = $table->getArguments();

                if ($herbId = $arguments['herb_id'] ?? null) {
                    $query->where('herb_id', $herbId);
                }

                return $query;
            })
            ->paginated(false)
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('herb.name')
                            ->weight(FontWeight::SemiBold),
                        TextColumn::make('specification'),
                    ])->space(true),
                    TextColumn::make('bestbefore')
                        ->icon(Heroicon::Calendar)
                        ->badge()
                        ->tooltip(fn(Carbon $state) => $state->format('d.m.Y'))
                        ->formatStateUsing(fn() => '')
                        ->extraAttributes(['class' => '*:py-1 *:px-2'])
                        ->color(fn(Carbon $state) => $state->isNowOrPast() ? 'danger' : 'gray')
                        ->grow(false),
                    TextColumn::make('charge')
                        ->badge()
                        ->color('primary')
                        ->extraAttributes(['class' => '*:py-1 *:px-2'])
                        ->icon(Heroicon::Hashtag)
                        ->grow(false),
                    BagAmountColumn::make('size')
                        ->grow(),
                ])
            ]);
    }
}
