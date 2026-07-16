<?php

namespace App\Enums;

enum UnitStatus: string
{
    case Vacant = 'vacant';
    case Rented = 'rented';
    case Reserved = 'reserved';
    case Maintenance = 'maintenance';
    case Unavailable = 'unavailable';
}
