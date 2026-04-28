<?php

namespace App\Filament\Resources\Labels\Pages;

use App\Filament\Resources\Labels\LabelResource;
use App\Labels\TemplateRegistry;
use App\Models\Label;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CreateLabel extends CreateRecord
{
    protected static string $resource = LabelResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Neues Etikett')
                    ->description('Wähle eine Vorlage und ggf. ein Endprodukt. Parameter und weitere Einstellungen können nach dem Erstellen bearbeitet werden.')
                    ->schema([
                        Select::make('template_key')
                            ->label('Vorlage')
                            ->options(fn () => app(TemplateRegistry::class)->options())
                            ->required()
                            ->live(),
                        TextInput::make('name')
                            ->label('Name')
                            ->maxLength(255),
                        MorphToSelect::make('labelable')
                            ->label('Zuordnung')
                            ->types(fn (Get $get) => LabelResource::morphTypesFor($get('template_key')))
                            ->searchable()
                            ->preload(),
                        Select::make('parent_id')
                            ->label('Eltern-Etikett')
                            ->relationship(
                                name: 'parent',
                                titleAttribute: 'name',
                                modifyQueryUsing: function ($query, Get $get) {
                                    $query->where('template_key', $get('template_key'));
                                },
                            )
                            ->getOptionLabelFromRecordUsing(
                                fn (Label $r) => $r->name ?: ('#'.$r->id)
                            )
                            ->searchable()
                            ->preload(),
                    ]),
            ])
            ->columns(1);
    }
}
