<?php

namespace App\Livewire;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Actions\Action;
use App\Settings\BillbeeSettings;
use BillbeeDe\BillbeeAPI\Client;
use Exception;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\Component as Livewire;

class EditBillbeeSettings extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public bool $testSuccess;

    public function mount(BillbeeSettings $settings): void
    {
        $this->form->fill($settings->toArray());
        $this->testSuccess = false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Billbee API Einstellungen')
                    ->description('Enthält alle Verbindungsdetails um mit der Billbee-API zu kommunizieren')
                    ->schema([
                        Toggle::make('enabled')
                            ->default(true)
                            ->live()
                            ->label('Billbee-Schnittstelle verwenden'),
                        TextInput::make('username')
                            ->label('Benutzername')
                            ->autocomplete(false)
                            ->live()
                            ->hidden(fn(Get $get) => !$get('enabled'))
                            ->hint('Dies ist normalerweise die Email-Adresse mit der du dich bei Billbee einloggst.'),
                        TextInput::make('password')
                            ->password()
                            ->autocomplete(false)
                            ->live()
                            ->hint('Das Passwort, welches du für die API-Verbindung angegeben hast.')
                            ->hidden(fn(Get $get) => !$get('enabled'))
                            ->label("Passwort"),
                        TextInput::make('key')
                            ->autocomplete(false)
                            ->label("API-Schlüssel")
                            ->live()
                            ->hidden(fn(Get $get) => !$get('enabled'))
                            ->hint('Der Schlüssel kann nur direkt beim Billbee-Support angefragt werden.')
                            ->mask('********-****-****-****-************')
                    ])->headerActions([
                        Action::make('test')
                            ->label('Verbindung testen')
                            ->color('info')
                            ->hidden(fn(Livewire $livewire) => $livewire->getPropertyValue('testSuccess'))
                            ->action(function (Action $action, Livewire $livewire) {
                                if ($livewire->test()) $action->sendSuccessNotification();
                                else $action->sendFailureNotification();
                            })
                            ->successNotificationTitle('Verbindungstest erfolgreich')
                            ->failureNotificationTitle('Verbindungstest fehlgeschlagen'),
                        Action::make('save')
                            ->label('Speichern')
                            ->color('primary')
                            ->visible(fn(Livewire $livewire) => $livewire->getPropertyValue('testSuccess'))
                            ->action('save')
                    ]),
                Section::make('Billbee Custom Shop Einstellungen')
                    ->description('Enthält den Zugriffsschlüssel, der Billbee ermöglicht Nachrichten an das KIS zu senden.')
                    ->schema([
                        TextInput::make('customShopKey')
                            ->autocomplete(false)
                            ->label("Zugangsschlüssel")
                            ->hint('Dieser Schlüssel muss in den Billbee Einstellungen hinterlegt werden.')
                    ])
                    ->headerActions([
                        Action::make('saveCustomShop')
                            ->label('Speichern')
                            ->color('primary')
                            ->action('saveCustomShop')
                    ])
            ])->statePath('data');
    }

    public function test(): bool
    {
        $data = $this->form->getState();

        try {
            $client = new Client($data['username'], $data['password'], $data['key']);

            $response = $client->provisioning()->getTermsInfo();
            if ($response->errorCode != 0) {
                $this->testSuccess = false;
            } else {
                $this->testSuccess = true;
            }
        } catch (Exception $e) {
            $this->testSuccess = false;
        }

        return $this->testSuccess;
    }

    public function updating(): void
    {
        $this->testSuccess = false;
    }

    public function saveCustomShop(BillbeeSettings $settings): void
    {
        $data = $this->form->getState();
        $settings->customShopKey = $data['customShopKey'];
        $settings->save();

        Notification::make()
            ->title('Billbee Custom Shop Einstellungen gespeichert')
            ->success()
            ->send();
    }

    public function save(BillbeeSettings $settings): void
    {
        $data = $this->form->getState();
        $settings->enabled = $data['enabled'];

        if ($settings->enabled) {
            $settings->username = $data['username'];
            $settings->password = $data['password'];
            $settings->key = $data['key'];
        }

        $settings->save();

        Notification::make()
            ->title('Billbee API Einstellungen gespeichert')
            ->success()
            ->send();
    }

    public function render(): View
    {
        return view('livewire.edit-billbee-settings');
    }
}
