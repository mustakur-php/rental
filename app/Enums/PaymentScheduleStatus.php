<?php

namespace App\Enums;

enum PaymentScheduleStatus: string
{
    case Pending = 'pending';
    case NearDue = 'near_due';
    case Due = 'due';
    case Partial = 'partial';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';
}
