<?php

namespace App\Filament\Resources\Suppliers\RelationManagers;

use App\Services\DocumentExtraction\CertificateExtractionAgent;
use App\Services\DocumentExtraction\DocumentExtractor;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

class CertificatesRelationManager extends RelationManager
{
    protected static string $relationship = 'certificates';

    protected static ?string $title = 'Zertifikate';

    protected static ?string $modelLabel = 'Zertifikat';

    protected static ?string $pluralModelLabel = 'Zertifikate';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                SpatieMediaLibraryFileUpload::make('document')
                    ->label('Dokument (PDF)')
                    ->collection('document')
                    ->acceptedFileTypes(['application/pdf'])
                    ->downloadable()
                    ->openable()
                    ->columnSpanFull(),
                Action::make('fillFromDocument')
                    ->label('Aus Dokument befüllen')
                    ->icon('heroicon-o-sparkles')
                    ->action(function (Get $get, Set $set): void {
                        $this->fillFromDocument($get, $set);
                    }),
                TextInput::make('certificate_number')
                    ->label('Zertifikatsnummer')
                    ->maxLength(255),
                TextInput::make('operator_name')
                    ->label('Unternehmer (Name)')
                    ->maxLength(255),
                TextInput::make('control_body')
                    ->label('Kontrollstelle')
                    ->maxLength(255),
                TextInput::make('control_body_code')
                    ->label('Kontrollstellen-Code')
                    ->hint('z.B. DE-ÖKO-006')
                    ->maxLength(255),
                TextInput::make('activities')
                    ->label('Tätigkeiten')
                    ->maxLength(255),
                TextInput::make('product_categories')
                    ->label('Erzeugniskategorien')
                    ->maxLength(255),
                DatePicker::make('valid_from')
                    ->label('Gültig ab'),
                DatePicker::make('valid_until')
                    ->label('Gültig bis'),
                DatePicker::make('issued_at')
                    ->label('Ausgestellt am'),
                TextInput::make('issued_place')
                    ->label('Ausstellungsort')
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('certificate_number')
                    ->label('Nummer')
                    ->searchable(),
                TextColumn::make('control_body_code')
                    ->label('Kontrollstelle'),
                TextColumn::make('valid_from')
                    ->label('Gültig ab')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('valid_until')
                    ->label('Gültig bis')
                    ->date('d.m.Y')
                    ->sortable(),
            ])
            ->defaultSort('valid_from', 'desc')
            ->headerActions([
                CreateAction::make(),
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

    /**
     * Synchronously extract certificate fields from the uploaded PDF and
     * fill the form for the user to review before saving.
     */
    private function fillFromDocument(Get $get, Set $set): void
    {
        $path = $this->uploadedDocumentPath($get('document'));

        if ($path === null) {
            Notification::make()
                ->title('Bitte zuerst ein PDF hochladen.')
                ->warning()
                ->send();

            return;
        }

        try {
            $data = app(DocumentExtractor::class)->fromPath(app(CertificateExtractionAgent::class), $path);
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Extraktion fehlgeschlagen')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        foreach ($data as $field => $value) {
            if ($value !== null && $value !== '') {
                $set($field, $value);
            }
        }

        Notification::make()
            ->title('Felder aus dem Dokument befüllt. Bitte prüfen.')
            ->success()
            ->send();
    }

    /**
     * Resolve the absolute path of the freshly-uploaded (not yet persisted) PDF.
     *
     * @param  mixed  $state  The SpatieMediaLibraryFileUpload state.
     */
    private function uploadedDocumentPath(mixed $state): ?string
    {
        $file = is_array($state) ? reset($state) : $state;

        if ($file instanceof TemporaryUploadedFile) {
            return $file->getRealPath();
        }

        return null;
    }
}
