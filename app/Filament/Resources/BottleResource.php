<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BottleResource\Pages;
use App\Filament\Resources\BottleResource\RelationManagers;
use App\Models\Bottle;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use function auth;
use function now;

class BottleResource extends Resource
{
    protected static ?string $model = Bottle::class;
    protected static ?string $modelLabel = 'Abfüllung';
    protected static ?string $pluralModelLabel = 'Abfüllungen';

    protected static ?string $navigationGroup = 'Bestand';
    protected static ?int $navigationSort = 10;
    protected static ?string $navigationIcon = 'heroicon-o-inbox';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('user_id')
                    ->label('Abfüller')
                    ->required()
                    ->relationship('user', 'name')
                    ->default(auth()->id()),
                DatePicker::make('date')
                    ->label('Abfülldatum')
                    ->required()
                    ->default(now()->format('Y-m-d')),
            ])->columns();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Abfülldatum')
                    ->date()
                    ->formatStateUsing(function ($state) {
                        return $state->format('d.m.Y');
                    }),
                Tables\Columns\TextColumn::make('note')
                    ->label('Notiz')
                    ->searchable(),
                Tables\Columns\TextColumn::make('positions')
                    ->label('Produkte')
                    ->formatStateUsing(function (string $state) {
                        $positions = json_decode($state, true);
                        return collect($positions)
                            ->pluck('variant.product.name')
                            ->join(', ');
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PositionsRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBottles::route('/'),
            'create' => Pages\CreateBottle::route('/create'),
            'edit' => Pages\EditBottle::route('/{record}/edit'),
            'recipes' => Pages\Recipes::route('/{record}/recipes'),
        ];
    }
}
