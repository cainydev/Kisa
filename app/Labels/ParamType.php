<?php

namespace App\Labels;

enum ParamType: string
{
    case String = 'string';
    case Number = 'number';
    case Image = 'image';
    case Color = 'color';
    case Font = 'font';
}
