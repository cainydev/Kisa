<?php

namespace App\Filament\Resources\BioInspectors;

use App\Enums\Country;
use App\Filament\Resources\BioInspectors\Pages\ManageBioInspectors;
use App\Models\BioInspector;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class BioInspectorResource extends Resource
{
    protected static ?string $model = BioInspector::class;

    protected static ?string $modelLabel = 'Bio-Kontrollstelle';

    protected static ?string $pluralModelLabel = 'Bio-Kontrollstellen';

    protected static ?string $recordTitleAttribute = 'company';

    protected static string|\UnitEnum|null $navigationGroup = 'Metadaten';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-check-badge';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('company')
                    ->label('Firma')
                    ->required()
                    ->maxLength(255),
                TextInput::make('label')
                    ->label('Kennzeichnung')
                    ->required()
                    ->maxLength(255),
                Select::make('country')
                    ->label('Land')
                    ->options(fn (): array => collect(Country::cases())
                        ->mapWithKeys(fn (Country $c): array => [$c->value => $c->flaggedLabel()->toHtml()])
                        ->all())
                    ->searchable()
                    ->allowHtml()
                    ->getSearchResultsUsing(fn (string $search): array => collect(Country::cases())
                        ->filter(fn (Country $c): bool => str_contains(Str::lower($c->getLabel()), Str::lower($search)))
                        ->mapWithKeys(fn (Country $c): array => [$c->value => $c->flaggedLabel()->toHtml()])
                        ->all()),
            ]);
    }

    /**
     * A canonical, reusable control-body picker. Renders each option as the
     * control code in a badge next to the company name, searchable on both.
     * Bind it to the owning relationship name (default "bioInspector").
     */
    public static function select(string $name = 'bio_inspector_id', string $relationship = 'bioInspector'): Select
    {
        return Select::make($name)
            ->label('Kontrollstelle')
            ->relationship($relationship, 'company')
            ->getOptionLabelFromRecordUsing(fn (BioInspector $record): HtmlString => $record->badgedLabel())
            ->searchable(['company', 'label'])
            ->allowHtml()
            ->preload();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company')
                    ->label('Firma')
                    ->searchable(),
                TextColumn::make('label')
                    ->label('Kennzeichnung')
                    ->searchable(),
                TextColumn::make('country')
                    ->label('Land')
                    ->formatStateUsing(fn (?Country $state): HtmlString => $state?->flaggedLabel() ?? new HtmlString(''))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBioInspectors::route('/'),
        ];
    }
}
