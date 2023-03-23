<?php

namespace App\Orchid\Screens\Information;

use App\Orchid\Layouts\Settings\BillbeeLayout;
use App\Orchid\Layouts\Settings\SettingsLayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class SettingsScreen extends Screen
{
    /**
     * Query data.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [];
    }

    /**
     * Display header name.
     */
    public function name(): ?string
    {
        return 'Einstellungen';
    }

    /**
     * Button commands.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Button::make('Speichern')
                ->type(Color::SUCCESS())
                ->icon('check')
                ->method('save'),
        ];
    }

    /**
     * Views.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [
            Layout::block(SettingsLayout::class)
                ->title(__('Allgemein'))
                ->description(__('Allgemeine Einstellungen')),
            Layout::block(BillbeeLayout::class)
                ->title(__('Billbee'))
                ->description(__('Einstellungen zur Billbee API')),
        ];
    }

    public function save(Request $request)
    {
        $settings = [
            'billbee' => [
                'everyXMinutes' => intval($request['billbee.everyXMinutes']),
                'from' => $request['billbee.from'],
                'to' => $request['billbee.to'],
                'get-stock' => boolval($request['billbee.get-stock']),
                'set-stock' => boolval($request['billbee.set-stock']),
            ],
        ];

        $data = var_export($settings, 1);

        if (File::put(config_path('kis.php'), "<?php\n return $data ;")) {
            Toast::success('Einstellungen gespeichert.');
        } else {
            Toast::error('Einstellungen konnten nicht gespeichert werden.');
        }
    }
}
