<?php

namespace App\Livewire;

use App\Settings\BillbeeSettings;
use BillbeeDe\BillbeeAPI\Client;
use Exception;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;

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

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Toggle::make('enabled')
                    ->live()
                    ->label('Billbee-Schnittstelle verwenden'),
                TextInput::make('username')
                    ->label('Benutzername')
                    ->autocomplete(false)
                    ->live()
                    ->requiredIf('enabled', true)
                    ->hidden(fn(Get $get) => !$get('enabled'))
                    ->hint('Dies ist normalerweise die Email-Adresse mit der du dich bei Billbee einloggst.'),
                TextInput::make('password')
                    ->password()
                    ->autocomplete(false)
                    ->live()
                    ->hint('Das Passwort, welches du für die API-Verbindung angegeben hast.')
                    ->requiredIf('enabled', true)
                    ->hidden(fn(Get $get) => !$get('enabled'))
                    ->label("Passwort"),
                TextInput::make('key')
                    ->autocomplete(false)
                    ->label("API-Schlüssel")
                    ->live()
                    ->requiredIf('enabled', true)
                    ->hidden(fn(Get $get) => !$get('enabled'))
                    ->hint('Der Schlüssel kann nur direkt beim Billbee-Support angefragt werden.')
                    ->mask('********-****-****-****-************')
            ])
            ->statePath('data');
    }

    public function updating(): void
    {
        $this->testSuccess = false;
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
            ->title('Billbee Einstellungen gespeichert')
            ->success()
            ->send();
    }

    public function test(): void
    {
        $data = $this->form->getState();

        try {
            $client = new Client($data['username'], $data['password'], $data['key']);

            $response = $client->provisioning()->getTermsInfo();
            if ($response->getErrorCode() != 0) {
                $this->testSuccess = false;
            } else {
                $this->testSuccess = true;
            }
        } catch (Exception $e) {
            $this->testSuccess = false;
        }
    }

    public function render(): View
    {
        return view('livewire.edit-billbee-settings');
    }
}
