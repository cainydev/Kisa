<?php

namespace App\Livewire;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Actions\Action;
use App\Settings\StatsSettings;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use function now;
use function view;

class EditStatsSettings extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(StatsSettings $settings): void
    {
        $this->form->fill($settings->toArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Periodische Generierung')
                    ->description('Wenn aktiviert, werden die Statistiken automatisch jeden Tag zur gewählten Uhrzeit generiert.')
                    ->schema([
                        Toggle::make('autoEnabled')
                            ->default(true)
                            ->label('Aktiviert'),
                        DatePicker::make('startDate')
                            ->label('Startdatum')
                            ->hint('Das Datum bis zu welchem die Statistiken in die Vergangenheit generiert werden.')
                            ->default(now()->subYear())
                            ->required(),
                        TimePicker::make('autoTime')
                            ->label('Uhrzeit')
                            ->default('02:00')
                            ->requiredIfAccepted('auto_enabled')
                            ->hint('Die Uhrzeit zu welcher die Statistiken generiert werden.'),
                    ])->headerActions([
                        Action::make('save')
                            ->label('Speichern')
                            ->color('primary')
                            ->action('save')
                    ]),
                Section::make('Empfohlene Abfüllungen')
                    ->schema([

                    ])
            ])->statePath('data');
    }

    public function save(StatsSettings $settings): void
    {
        $this->form->validate();

        $settings->fill($this->form->getState());
        $settings->save();

        Notification::make()
            ->title('Statistik Einstellungen gespeichert')
            ->success()
            ->send();
    }

    public function render(): View
    {
        return view('livewire.edit-stats-settings');
    }
}
