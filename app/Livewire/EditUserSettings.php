<?php

namespace App\Livewire;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

class EditUserSettings extends Component implements HasForms
{
    use InteractsWithForms, WithFileUploads;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            ...(auth()->user()->toArray()),
            'avatar' => auth()->user()->getFirstMedia('avatar')
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Flex::make([
                    SpatieMediaLibraryFileUpload::make('avatar')
                        ->collection('avatar')
                        ->label('Profilbild')
                        ->image()
                        ->imageEditor()
                        ->imageEditorAspectRatios(['1:1'])
                        ->imageEditorViewportWidth('1080')
                        ->imageEditorViewportHeight('1080')
                        ->avatar()
                        ->model(auth()->user())
                        ->grow(false),
                    Grid::make(['default' => 1])->schema([
                        TextInput::make('name')
                            ->label('Benutzername')
                            ->required()
                            ->autocomplete(false),
                        TextInput::make('email')
                            ->email()
                            ->required()
                    ]),
                ])
            ])->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        auth()->user()->update([
            'name' => $state['name'],
            'email' => $state['email'],
        ]);

        Notification::make()
            ->title('Benutzer Einstellungen gespeichert')
            ->success()
            ->send();
    }

    public function render(): View
    {
        return view('livewire.edit-user-settings');
    }
}
