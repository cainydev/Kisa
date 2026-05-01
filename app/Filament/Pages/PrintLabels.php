<?php

namespace App\Filament\Pages;

use App\Labels\LabelTemplate;
use App\Labels\TemplateRegistry;
use App\Models\Label;
use App\Models\Product;
use App\Models\Variant;
use App\Services\Labels\CmykConverter;
use App\Services\Labels\LabelRenderer;
use App\Services\Labels\RenderOptions;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Url;
use UnitEnum;

class PrintLabels extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-printer';

    protected static ?string $navigationLabel = 'Etiketten drucken';

    protected static string|null|UnitEnum $navigationGroup = 'Produkte';

    protected static ?int $navigationSort = 51;

    protected string $view = 'filament.pages.print-labels';

    public ?array $data = [
        'subject_type' => null,
        'subject_id' => null,
    ];

    #[Url(as: 'type', except: '')]
    public string $subjectType = '';

    #[Url(as: 'id', except: '')]
    public string $subjectId = '';

    public function mount(): void
    {
        $this->data = [
            'subject_type' => $this->subjectType ?: null,
            'subject_id' => $this->subjectId ?: null,
        ];
        $this->form->fill($this->data);
    }

    public function updatedData(): void
    {
        $this->subjectType = (string) ($this->data['subject_type'] ?? '');
        $this->subjectId = (string) ($this->data['subject_id'] ?? '');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Was möchtest du drucken?')
                    ->schema([
                        Select::make('subject_type')
                            ->label('Entitätstyp')
                            ->options(fn () => self::subjectTypeOptions())
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($state, $set) => $set('subject_id', null)),
                        Select::make('subject_id')
                            ->label('Entität')
                            ->options(fn (Get $get) => self::subjectOptions($get('subject_type')))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->disabled(fn (Get $get) => ! $get('subject_type'))
                            ->required(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    /**
     * @return array<string, string>
     */
    public static function subjectTypeOptions(): array
    {
        $types = [];
        foreach (app(TemplateRegistry::class)->all() as $template) {
            foreach ($template->subjects() as $class) {
                $types[$class] = self::humanType($class);
            }
        }

        return $types;
    }

    /**
     * @return array<int, string>
     */
    public static function subjectOptions(?string $type): array
    {
        if (! $type || ! class_exists($type)) {
            return [];
        }

        return $type::query()
            ->orderBy(self::titleFor($type))
            ->limit(500)
            ->get()
            ->mapWithKeys(fn (Model $m) => [$m->getKey() => $m->{self::titleFor($type)} ?? ('#'.$m->getKey())])
            ->all();
    }

    protected static function humanType(string $class): string
    {
        return match ($class) {
            Product::class => 'Endprodukt',
            Variant::class => 'Variante',
            default => class_basename($class),
        };
    }

    protected static function titleFor(string $class): string
    {
        return match ($class) {
            Variant::class => 'sku',
            default => 'name',
        };
    }

    /**
     * Targets the page can render for the currently selected entity.
     *
     * @return array<int, array{
     *   key: string,
     *   kind: 'bare'|'configured'|'unconfigured',
     *   template: LabelTemplate,
     *   label: ?Label,
     *   pages: array<string,string>,
     *   reason?: string,
     * }>
     */
    public function getTargetsProperty(): array
    {
        $type = $this->data['subject_type'] ?? null;
        $id = $this->data['subject_id'] ?? null;
        if (! $type || ! $id || ! class_exists($type)) {
            return [];
        }
        /** @var Model|null $entity */
        $entity = $type::find($id);
        if (! $entity) {
            return [];
        }

        $registry = app(TemplateRegistry::class);
        $targets = [];

        // 1) Configured labels for this entity (entity has morphMany('labels'))
        $labels = method_exists($entity, 'labels') ? $entity->labels()->get() : collect();

        foreach ($labels as $label) {
            if (! $registry->has($label->template_key)) {
                continue;
            }
            $template = $registry->get($label->template_key);
            $targets[] = [
                'key' => "label-{$label->id}",
                'kind' => 'configured',
                'template' => $template,
                'label' => $label,
                'pages' => $template->pages(),
            ];
        }

        // 2) Templates that apply to the entity. For each template:
        //    - if a Label already covers it (configured), skip (already listed above).
        //    - else if it can render bare → bare.
        //    - else → unconfigured (offer to configure).
        $coveredTemplateKeys = $labels->pluck('template_key')->all();
        foreach ($registry->all() as $template) {
            if (! $template->appliesTo($entity)) {
                continue;
            }
            if (in_array($template->key(), $coveredTemplateKeys, true)) {
                continue;
            }
            if (method_exists($template, 'canRenderBare') && $template->canRenderBare()) {
                $targets[] = [
                    'key' => "bare-{$template->key()}",
                    'kind' => 'bare',
                    'template' => $template,
                    'label' => null,
                    'pages' => $template->pages(),
                ];
            } else {
                $targets[] = [
                    'key' => "unconfigured-{$template->key()}",
                    'kind' => 'unconfigured',
                    'template' => $template,
                    'label' => null,
                    'pages' => [],
                    'reason' => 'Erfordert Werte (z.B. Bild). Bitte zuerst konfigurieren.',
                ];
            }
        }

        return $targets;
    }

    /**
     * Plain RGB PDF — for screen, internal review, normal printing.
     */
    public function pdfAction(): Action
    {
        return Action::make('pdf')
            ->label('PDF')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->action(fn (array $arguments) => $this->generatePdf(
                $arguments,
                new RenderOptions(bleed_mm: 0, marks: false, cmyk: false),
            ));
    }

    /**
     * Print-ready PDF — bleed + crop marks + CMYK conversion. For the printer.
     */
    public function printReadyPdfAction(): Action
    {
        return Action::make('printReadyPdf')
            ->label('PrintReady™ PDF')
            ->icon('heroicon-o-printer')
            ->color('primary')
            ->action(fn (array $arguments) => $this->generatePdf(
                $arguments,
                new RenderOptions(bleed_mm: 3, marks: true, cmyk: true, checkOverflow: true),
            ));
    }

    protected function generatePdf(array $arguments, RenderOptions $opts): mixed
    {
        $templateKey = $arguments['template'] ?? null;
        $labelId = $arguments['label'] ?? null;
        $pageKey = $arguments['page'] ?? null;
        $entityType = $this->data['subject_type'] ?? null;
        $entityId = $this->data['subject_id'] ?? null;

        $registry = app(TemplateRegistry::class);
        if (! $templateKey || ! $registry->has($templateKey) || ! $pageKey) {
            Notification::make()->title('Ungültige Auswahl')->danger()->send();

            return null;
        }
        $template = $registry->get($templateKey);

        $label = $labelId ? Label::find($labelId) : null;
        $entity = ($entityType && $entityId && class_exists($entityType))
            ? $entityType::find($entityId)
            : null;

        try {
            $renderer = app(LabelRenderer::class);
            $path = $renderer->renderPagePdf($template, $pageKey, $label, $entity, $opts);

            if ($renderer->lastOverflow === true) {
                Notification::make()
                    ->title('Überlauf erkannt')
                    ->body('Der Inhalt passt nicht vollständig in die Etikettenfläche. Bitte vor dem Drucken prüfen.')
                    ->warning()
                    ->persistent()
                    ->send();
            }

            // Always run through Ghostscript to set exact MediaBox/TrimBox/BleedBox
            // (Browsershot's mm→pt conversion drifts by ~1pt). Convert to CMYK only
            // for the print-ready variant.
            $dims = $template->dimensions();
            $markLen = 5; // mm — keep in sync with chassis component & LabelRenderer
            $marginToTrim = $opts->bleed_mm + ($opts->marks ? $markLen : 0);
            $path = app(CmykConverter::class)->convert(
                inPath: $path,
                trimWidthMm: $dims['width_mm'],
                trimHeightMm: $dims['height_mm'],
                bleedMm: $opts->bleed_mm,
                marginToTrimMm: $marginToTrim,
                cmyk: $opts->cmyk,
            );
            $name = self::fileNameFor($template, $pageKey, $label, $entity, $opts);

            return response()->download($path, $name)->deleteFileAfterSend();
        } catch (\Throwable $e) {
            Notification::make()->title('Fehler')->body($e->getMessage())->danger()->send();
            throw $e;
        }
    }

    protected static function fileNameFor(LabelTemplate $template, string $pageKey, ?Label $label, ?Model $entity, RenderOptions $opts): string
    {
        $base = $label?->name
            ?: ($entity?->name ?? $template->name());

        $base = strtr($base, [
            'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss',
            'Ä' => 'Ae', 'Ö' => 'Oe', 'Ü' => 'Ue',
        ]);
        $slug = preg_replace('/[^A-Za-z0-9._-]+/', '-', "{$base}-{$pageKey}");
        $slug = trim($slug, '-');

        $suffix = $opts->cmyk ? '-printready' : '';

        return $slug.$suffix.'.pdf';
    }
}
