<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Registered = 'registered';
    case Cancelled = 'cancelled';
    case Returned = 'returned';
}
