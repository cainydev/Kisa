<?php

namespace App\Filament\Resources\Labels\Pages;

use App\Filament\Resources\Labels\LabelResource;
use App\Labels\TemplateRegistry;
use App\Models\Label;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;

class EditLabel extends EditRecord
{
    protected static string $resource = LabelResource::class;

    protected string $view = 'filament.resources.labels.pages.edit-label';

    #[Url(as: 'page', except: '')]
    public string $currentPage = '';

    #[Url(as: 'zoom', except: 'fit')]
    public string $zoom = 'fit';

    public int $previewBust = 0;

    /** 'saved' | 'error' */
    public string $saveStatus = 'saved';

    public ?string $saveError = null;

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->saveStatus = 'saved';
        $this->saveError = null;

        $pages = $this->templatePages();
        if (! array_key_exists($this->currentPage, $pages)) {
            $this->currentPage = (string) (array_key_first($pages) ?? '');
        }
    }

    /**
     * @return array<string, string>
     */
    public function templatePages(): array
    {
        $registry = app(TemplateRegistry::class);
        if (! $registry->has($this->record->template_key)) {
            return [];
        }

        return $registry->get($this->record->template_key)->pages();
    }

    public function goToPage(string $key): void
    {
        if (array_key_exists($key, $this->templatePages())) {
            $this->currentPage = $key;
        }
    }

    public function nextPage(): void
    {
        $keys = array_keys($this->templatePages());
        if (! $keys) {
            return;
        }
        $i = array_search($this->currentPage, $keys, true);
        $i = $i === false ? 0 : (($i + 1) % count($keys));
        $this->currentPage = $keys[$i];
    }

    public function previousPage(): void
    {
        $keys = array_keys($this->templatePages());
        if (! $keys) {
            return;
        }
        $i = array_search($this->currentPage, $keys, true);
        $i = $i === false ? 0 : ($i - 1 + count($keys)) % count($keys);
        $this->currentPage = $keys[$i];
    }

    public function previewUrl(): ?string
    {
        $pages = $this->templatePages();
        if (! $pages || ! $this->currentPage) {
            return null;
        }

        $version = ($this->record->updated_at?->getTimestamp() ?? 0).'-'.$this->previewBust;

        return route('labels.preview', [
            'label' => $this->record->getKey(),
            'page' => $this->currentPage,
        ]).'?v='.$version;
    }

    public function reloadPreview(): void
    {
        $this->previewBust++;
    }

    public function templateDimensions(): ?array
    {
        $registry = app(TemplateRegistry::class);
        if (! $registry->has($this->record->template_key)) {
            return null;
        }

        return $registry->get($this->record->template_key)->dimensions();
    }

    /**
     * Autosave: called by Livewire whenever any field in $data is updated.
     * Debounced from the front-end via wire:model.lazy / wire:change.
     */
    private bool $autosaving = false;

    public function autosave(): void
    {
        if ($this->autosaving) {
            return;
        }
        $this->autosaving = true;
        try {
            $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
            $this->saveStatus = 'saved';
            $this->saveError = null;
            $this->previewBust++;
        } catch (ValidationException $e) {
            $this->saveStatus = 'error';
            $messages = collect($e->errors())->flatten()->all();
            $this->saveError = implode(' · ', $messages) ?: $e->getMessage();
        } catch (\Throwable $e) {
            $this->saveStatus = 'error';
            $this->saveError = $e->getMessage();
            \Log::error('Label autosave failed', ['exception' => $e]);
        } finally {
            $this->autosaving = false;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Drucken')
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->url(fn () => $this->currentPage
                    ? route('labels.preview', [
                        'label' => $this->record->getKey(),
                        'page' => $this->currentPage,
                    ]).'?format=pdf'
                    : null)
                ->openUrlInNewTab(),
            Action::make('openParent')
                ->label('Eltern öffnen')
                ->icon('heroicon-o-arrow-up')
                ->color('gray')
                ->visible(fn () => $this->record->parent_id !== null)
                ->url(fn () => $this->record->parent_id
                    ? LabelResource::getUrl('edit', ['record' => $this->record->parent_id])
                    : null),
            Action::make('createChild')
                ->label('Untergeordnetes Etikett anlegen')
                ->icon('heroicon-o-plus')
                ->color('gray')
                ->action(function () {
                    $child = Label::create([
                        'template_key' => $this->record->template_key,
                        'parent_id' => $this->record->id,
                    ]);

                    $this->redirect(LabelResource::getUrl('edit', ['record' => $child->id]));
                }),
            Action::make('createSibling')
                ->label('Geschwister-Etikett anlegen')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->visible(fn () => $this->record->parent_id !== null)
                ->action(function () {
                    $sibling = Label::create([
                        'template_key' => $this->record->template_key,
                        'parent_id' => $this->record->parent_id,
                    ]);

                    $this->redirect(LabelResource::getUrl('edit', ['record' => $sibling->id]));
                }),
            DeleteAction::make(),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return null;
    }
}
