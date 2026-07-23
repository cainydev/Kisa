<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Operator activities as defined by the EU organic regulation and printed on
 * an organic certificate. A certificate carries one or more of these.
 * Backing values are the German terms as they appear on the document.
 */
enum CertificateActivity: string implements HasLabel
{
    case Production = 'Erzeugung';
    case Preparation = 'Aufbereitung';
    case Labelling = 'Kennzeichnung';
    case Marketing = 'Inverkehrbringen';
    case Storage = 'Lagerung';
    case Import = 'Einfuhr';
    case Export = 'Ausfuhr';

    public function getLabel(): string
    {
        return match ($this) {
            self::Production => 'Erzeugung',
            self::Preparation => 'Aufbereitung',
            self::Labelling => 'Kennzeichnung',
            self::Marketing => 'Inverkehrbringen',
            self::Storage => 'Lagerung',
            self::Import => 'Einfuhr',
            self::Export => 'Ausfuhr',
        };
    }
}
