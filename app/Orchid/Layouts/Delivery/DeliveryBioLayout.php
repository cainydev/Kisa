<?php

namespace App\Orchid\Layouts\Delivery;

use Orchid\Screen\Fields\{DateTimer, CheckBox, Input};
use Orchid\Screen\Layouts\Rows;

class DeliveryBioLayout extends Rows
{
    protected function fields(): iterable
    {
        return [
            DateTimer::make('delivery.bio_inspection.date')
                ->title('Kontrolldatum')
                ->value(now())
                ->required()
                ->format('Y-m-d'),
            Checkbox::make('delivery.bio_inspection.hasInvoice')
                ->title('Rechnung vorhanden?')
                ->sendTrueOrFalse()
                ->value($this->query->has('delivery.bio_inspection.hasInvoice') ? $this->query->get('delivery.bio_inspection.hasInvoice') : true),
            Checkbox::make('delivery.bio_inspection.codeOnInvoice')
                ->title('Codenummer der Kontrollstelle auf Rechnung?')
                ->sendTrueOrFalse()
                ->value($this->query->has('delivery.bio_inspection.codeOnInvoice') ? $this->query->get('delivery.bio_inspection.codeOnInvoice') : true),
            Checkbox::make('delivery.bio_inspection.hasDeliveryNote')
                ->title('Lieferschein vorhanden?')
                ->sendTrueOrFalse()
                ->value($this->query->has('delivery.bio_inspection.hasDeliveryNote') ? $this->query->get('delivery.bio_inspection.hasDeliveryNote') : true),
            Checkbox::make('delivery.bio_inspection.codeOnDeliveryNote')
                ->title('Codenummer der Kontrollstelle auf Lieferschein?')
                ->sendTrueOrFalse()
                ->value($this->query->has('delivery.bio_inspection.codeOnDeliveryNote') ? $this->query->get('delivery.bio_inspection.codeOnDeliveryNote') : false),
            Checkbox::make('delivery.bio_inspection.codeOnBag')
                ->title('Codenummer der Kontrollstelle auf Gebinde?')
                ->sendTrueOrFalse()
                ->value($this->query->has('delivery.bio_inspection.codeOnBag') ? $this->query->get('delivery.bio_inspection.codeOnBag') : true),
            CheckBox::make('delivery.bio_inspection.certificateValid')
                ->title('Codenummer der Kontrollstelle gültig?')
                ->sendTrueOrFalse()
                ->value($this->query->has('delivery.bio_inspection.certificateValid') ? $this->query->get('delivery.bio_inspection.certificateValid') : true),
            CheckBox::make('delivery.bio_inspection.goodsMatchValidity')
                ->title('Entspricht die Ware dem Zertizierungsbereich?')
                ->sendTrueOrFalse()
                ->value($this->query->has('delivery.bio_inspection.goodsMatchValidity') ? $this->query->get('delivery.bio_inspection.goodsMatchValidity') : true),
            Checkbox::make('delivery.bio_inspection.damaged')
                ->title('Optische Kontrolle: Beschädigung?')
                ->sendTrueOrFalse()
                ->value($this->query->has('delivery.bio_inspection.damaged') ? $this->query->get('delivery.bio_inspection.damaged') : false),
            CheckBox::make('delivery.bio_inspection.pestInfection')
                ->title('Optische Kontrolle: Schädlingsbefall?')
                ->sendTrueOrFalse()
                ->value($this->query->has('delivery.bio_inspection.pestInfection') ? $this->query->get('delivery.bio_inspection.pestInfection') : false),
            Input::make('delivery.bio_inspection.notes')
                ->title('Bei Befund (Bemerkungen):'),
            CheckBox::make('delivery.bio_inspection.approved')
                ->title('Ware freigegeben?')
                ->sendTrueOrFalse()
                ->value($this->query->has('delivery.bio_inspection.approved') ? $this->query->get('delivery.bio_inspection.approved') : true),

        ];
    }
}
