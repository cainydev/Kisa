<?php

namespace App\Filament\Resources\Labels;

use App\Filament\Resources\Labels\Pages\CreateLabel;
use App\Filament\Resources\Labels\Pages\EditLabel;
use App\Filament\Resources\Labels\Pages\ListLabels;
use App\Labels\LabelTemplate;
use App\Labels\Param;
use App\Labels\ParameterResolver;
use App\Labels\ParamType;
use App\Labels\TemplateRegistry;
use App\Models\Label;
use App\Models\Variant;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\MorphToSelect\Type;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class LabelResource extends Resource
{
    protected static ?string $model = Label::class;

    protected static ?string $modelLabel = 'Etikett';

    protected static ?string $pluralModelLabel = 'Etiketten';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 50;

    protected static string|\UnitEnum|null $navigationGroup = 'Produkte';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)
                ->key('topBar')
                ->schema(self::topBarComponents()),
            Section::make('Parameter')
                ->key('parameters')
                ->description('Lass Felder leer, um den Standardwert der Vorlage (oder eines übergeordneten Etiketts) zu übernehmen.')
                ->schema(fn (Get $get) => self::parameterFields($get('template_key'), $get('parent_id')))
                ->columns(2),
        ]);
    }

    /**
     * The fields that live in the top bar of the EditLabel page (Vorlage, Bezeichnung, Basiert auf).
     *
     * @return array<Component>
     */
    public static function topBarComponents(): array
    {
        $autosave = fn ($livewire) => method_exists($livewire, 'autosave') ? $livewire->autosave() : null;

        return [
            Fieldset::make('Stammdaten')
                ->schema([
                    TextInput::make('name')
                        ->label('Name')
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated($autosave),
                    Select::make('template_key')
                        ->label('Vorlage')
                        ->options(fn () => app(TemplateRegistry::class)->options())
                        ->required()
                        ->live()
                        ->disabled(fn (?Label $record) => $record !== null)
                        ->dehydrated(),
                    Select::make('parent_id')
                        ->label('Eltern-Etikett')
                        ->relationship(
                            name: 'parent',
                            titleAttribute: 'name',
                            modifyQueryUsing: function (Builder $query, ?Label $record, Get $get) {
                                $query->where('template_key', $get('template_key'));
                                if ($record) {
                                    $exclude = self::descendantIds($record);
                                    $exclude[] = $record->id;
                                    $query->whereNotIn('id', $exclude);
                                }
                            },
                        )
                        ->getOptionLabelFromRecordUsing(
                            fn (Label $r) => $r->name ?: ('#'.$r->id)
                        )
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated($autosave),
                ])
                ->columns(3),
            MorphToSelect::make('labelable')
                ->label('Zuordnung')
                ->types(fn (Get $get) => self::morphTypesFor($get('template_key')))
                ->searchable()
                ->preload()
                ->columns(2)
                ->modifyTypeSelectUsing(fn (Select $select) => $select->label('Typ')->hiddenLabel(false))
                ->modifyKeySelectUsing(fn (Select $select) => $select
                    ->label('Eintrag')
                    ->hiddenLabel(false)
                    ->live()
                    ->afterStateUpdated($autosave)),
        ];
    }

    /**
     * @return array<Type>
     */
    public static function morphTypesFor(?string $templateKey): array
    {
        if (! $templateKey) {
            return [];
        }
        $registry = app(TemplateRegistry::class);
        if (! $registry->has($templateKey)) {
            return [];
        }
        $template = $registry->get($templateKey);

        $types = [];
        foreach ($template->subjects() as $class) {
            $types[] = Type::make($class)
                ->titleAttribute(self::titleAttributeFor($class));
        }

        return $types;
    }

    protected static function titleAttributeFor(string $class): string
    {
        return match ($class) {
            Variant::class => 'sku',
            default => 'name',
        };
    }

    /**
     * The fields that go in the Parameter pane on EditLabel.
     *
     * @return array<Component>
     */
    public static function parameterComponents(?string $templateKey, mixed $parentId = null): array
    {
        return self::parameterFields($templateKey, $parentId);
    }

    /**
     * @return array<Component>
     */
    protected static function parameterFields(?string $templateKey, mixed $parentId = null): array
    {
        if (! $templateKey) {
            return [
                Section::make('Vorlage wählen')
                    ->description('Wähle erst eine Vorlage, um die zugehörigen Parameter zu sehen.')
                    ->schema([])
                    ->columnSpanFull(),
            ];
        }
        $registry = app(TemplateRegistry::class);
        if (! $registry->has($templateKey)) {
            return [];
        }
        $template = $registry->get($templateKey);
        $parent = ! empty($parentId) ? Label::find($parentId) : null;

        $fields = [];
        foreach ($template->parameters() as $param) {
            if ($param->isShared() && $parent && $parent->hasAncestorValue($param->key(), $param->type())) {
                continue;
            }
            $fields[] = self::fieldFor($param, $template);
        }
        if (! $fields) {
            $fields[] = Section::make('Keine Parameter')
                ->description('Diese Vorlage hat keine überschreibbaren Werte.')
                ->schema([])
                ->columnSpanFull();
        }

        return $fields;
    }

    protected static function fieldFor(Param $param, LabelTemplate $template): Component
    {
        $key = $param->key();
        $label = $param->humanLabel();
        $hint = self::defaultHint($param);
        $autosave = fn ($livewire) => method_exists($livewire, 'autosave') ? $livewire->autosave() : null;
        $placeholder = fn (?Label $record) => self::placeholderFor($param, $record);

        $revert = Action::make("revert_{$key}")
            ->label('Erben')
            ->icon('heroicon-m-arrow-uturn-left')
            ->color('gray')
            ->size('xs')
            ->visible(fn ($state) => $state !== null && $state !== '')
            ->action(function (Set $set, $livewire) use ($key) {
                $set("parameters.{$key}", null);
                if (method_exists($livewire, 'autosave')) {
                    $livewire->autosave();
                }
            });

        return match ($param->type()) {
            ParamType::Image => SpatieMediaLibraryFileUpload::make("param_{$key}_upload")
                ->label($label)
                ->collection("param_{$key}")
                ->disk('public')
                ->visibility('public')
                ->preserveFilenames()
                ->image()
                ->imageEditor()
                ->imageEditorAspectRatioOptions([null, '1:1', '4:3', '3:4', '16:9', '9:16'])
                ->imageEditorMode(2)
                ->downloadable()
                ->maxFiles(1)
                ->helperText($hint)
                ->live()
                ->afterStateUpdated($autosave)
                ->columnSpanFull(),
            ParamType::Font => SpatieMediaLibraryFileUpload::make("param_{$key}_upload")
                ->label($label)
                ->collection("param_{$key}")
                ->disk('public')
                ->visibility('public')
                ->preserveFilenames()
                ->acceptedFileTypes([
                    'font/otf',
                    'font/ttf',
                    'font/woff',
                    'font/woff2',
                    'application/vnd.ms-opentype',
                    'application/font-sfnt',
                    'application/x-font-otf',
                    'application/x-font-ttf',
                ])
                ->mimeTypeMap([
                    'otf' => 'font/otf',
                    'ttf' => 'font/ttf',
                    'woff' => 'font/woff',
                    'woff2' => 'font/woff2',
                ])
                ->downloadable()
                ->maxFiles(1)
                ->helperText($hint)
                ->live()
                ->afterStateUpdated($autosave)
                ->columnSpanFull(),
            ParamType::Number => TextInput::make("parameters.{$key}")
                ->label($label)
                ->numeric()
                ->when(
                    $param->hasRange(),
                    fn (TextInput $input) => $input
                        ->minValue($param->rangeMin())
                        ->maxValue($param->rangeMax())
                        ->step($param->rangeStep() ?? 1)
                        ->suffix($param->rangeSuffix())
                )
                ->helperText($hint)
                ->placeholder($placeholder)
                ->live(onBlur: true)
                ->afterStateUpdated($autosave)
                ->hintAction($revert),
            ParamType::String => TextInput::make("parameters.{$key}")
                ->label($label)
                ->helperText($hint)
                ->placeholder($placeholder)
                ->live(onBlur: true)
                ->afterStateUpdated($autosave)
                ->hintAction($revert),
            ParamType::Color => ColorPicker::make("parameters.{$key}")
                ->label($label)
                ->helperText($hint)
                ->placeholder($placeholder)
                ->live(onBlur: true)
                ->afterStateUpdated($autosave)
                ->hintAction($revert),
            ParamType::Boolean => Toggle::make("parameters.{$key}")
                ->label($label)
                ->helperText($hint)
                ->live()
                ->afterStateUpdated($autosave)
                ->hintAction($revert),
            ParamType::Select => Select::make("parameters.{$key}")
                ->label($label)
                ->options($param->selectOptions())
                ->native(false)
                ->helperText($hint)
                ->placeholder($placeholder)
                ->live()
                ->afterStateUpdated($autosave)
                ->hintAction($revert),
        };
    }

    /**
     * Compute what the parameter value would be if this label had no local override.
     * Used as the form-field placeholder so the user sees the inherited / auto value.
     */
    protected static function placeholderFor(Param $param, ?Label $record): ?string
    {
        try {
            $resolver = app(ParameterResolver::class);
            $entity = $record?->labelable;

            // Walk parents (skip the current label so any local override is ignored).
            if ($record?->parent) {
                foreach ($record->parent->ancestorChain() as $ancestor) {
                    if ($param->type() === ParamType::Image) {
                        return null; // images have their own thumbnail
                    }
                    $params = $ancestor->parameters ?? [];
                    if (array_key_exists($param->key(), $params) && $params[$param->key()] !== null && $params[$param->key()] !== '') {
                        return (string) $params[$param->key()];
                    }
                }
            }

            if ($param->hasAutoDefault()) {
                $value = $param->resolveAuto($entity);
                if ($value !== null && $value !== '') {
                    return (string) $value;
                }
            }
            if ($param->hasLiteralDefault()) {
                $val = $param->literalDefault();
                if ($val === null || $val === '') {
                    return null;
                }

                return (string) $val;
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    protected static function defaultHint(Param $param): ?string
    {
        if ($param->hasAutoDefault()) {
            return 'Standard: aus der zugewiesenen Entität.';
        }
        if ($param->hasLiteralDefault()) {
            $val = $param->literalDefault();
            if ($val === '' || $val === null) {
                return 'Standard: leer.';
            }

            return 'Standard: '.(is_scalar($val) ? (string) $val : 'fest in der Vorlage.');
        }
        if ($param->isRequired()) {
            return 'Pflichtwert (kein Standard, muss hier oder im Eltern-Etikett gesetzt sein).';
        }

        return null;
    }

    /**
     * @return array<int>
     */
    protected static function descendantIds(Label $label): array
    {
        $ids = [];
        $queue = [$label->id];
        while ($queue) {
            $batch = Label::whereIn('parent_id', $queue)->pluck('id')->all();
            $ids = array_merge($ids, $batch);
            $queue = $batch;
        }

        return $ids;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                [$orderedIds, $treeMeta] = self::treeOrderAndDepths();
                app()->instance('labels.tree-meta', $treeMeta);
                if (empty($orderedIds)) {
                    return $query;
                }
                $placeholders = implode(',', array_fill(0, count($orderedIds), '?'));

                return $query->orderByRaw("FIELD(id, {$placeholders})", $orderedIds);
            })
            ->defaultPaginationPageOption(50)
            ->paginated([25, 50, 100, 'all'])
            ->columns([
                TextColumn::make('template_key')
                    ->label('Vorlage')
                    ->formatStateUsing(function (string $state) {
                        $r = app(TemplateRegistry::class);

                        return $r->has($state) ? $r->get($state)->name() : $state;
                    }),
                TextColumn::make('name')
                    ->label('Name')
                    ->placeholder('—')
                    ->searchable()
                    ->formatStateUsing(function (?string $state, Label $record) {
                        $meta = app()->bound('labels.tree-meta') ? app('labels.tree-meta') : [];
                        $prefix = $meta[$record->id]['prefix'] ?? '';
                        $label = $state ?: '—';
                        if ($prefix === '') {
                            return $label;
                        }

                        return new HtmlString(
                            '<span style="white-space:pre;color:#fff;font-family:ui-monospace,monospace">'
                            .e($prefix).'</span>'.e($label)
                        );
                    })
                    ->html(),
                TextColumn::make('labelable')
                    ->label('Zuordnung')
                    ->getStateUsing(function (Label $r) {
                        if (! $r->labelable_type) {
                            return 'Basis';
                        }
                        $entity = $r->labelable;
                        $type = class_basename($r->labelable_type);
                        $name = $entity?->name ?? ('#'.$r->labelable_id);

                        return "{$type}: {$name}";
                    }),
                TextColumn::make('parent.name')
                    ->label('Eltern-Etikett')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('template_key')
                    ->label('Vorlage')
                    ->options(fn () => app(TemplateRegistry::class)->options()),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Preorder traversal of the label hierarchy with per-row tree metadata.
     *
     * Returns:
     *   - array<int, int>             ordered list of label ids (roots first,
     *                                 each followed by its descendants depth-first)
     *   - array<int, array{depth:int, prefix:string}>
     *                                 metadata keyed by id. `prefix` is the
     *                                 ready-to-render box-drawing prefix
     *                                 (e.g. `│  ├─ `) for that row.
     *
     * @return array{0: array<int,int>, 1: array<int, array{depth:int, prefix:string}>}
     */
    protected static function treeOrderAndDepths(): array
    {
        $rows = Label::query()
            ->orderBy('parent_id')
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'parent_id'])
            ->all();

        $childrenByParent = [];
        foreach ($rows as $row) {
            $childrenByParent[$row->parent_id ?? 0][] = $row->id;
        }

        $ordered = [];
        $meta = [];
        // $ancestorIsLast[d] = true if the ancestor at depth d is the last
        // child of its parent (so deeper columns render a space rather than `│`).
        $walk = function (?int $parentId, int $depth, array $ancestorIsLast) use (&$walk, &$ordered, &$meta, $childrenByParent): void {
            $children = $childrenByParent[$parentId ?? 0] ?? [];
            $count = count($children);
            foreach ($children as $i => $id) {
                $isLast = ($i === $count - 1);
                $prefix = '';
                for ($d = 0; $d < $depth; $d++) {
                    $prefix .= ($ancestorIsLast[$d] ?? false) ? '   ' : '│  ';
                }
                if ($depth > 0) {
                    $prefix .= $isLast ? '└─ ' : '├─ ';
                }
                $ordered[] = $id;
                $meta[$id] = ['depth' => $depth, 'prefix' => $prefix];
                $walk($id, $depth + 1, [...$ancestorIsLast, $isLast]);
            }
        };
        $walk(null, 0, []);

        return [$ordered, $meta];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLabels::route('/'),
            'create' => CreateLabel::route('/create'),
            'edit' => EditLabel::route('/{record}/edit'),
        ];
    }
}
