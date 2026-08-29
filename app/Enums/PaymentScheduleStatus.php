<?php

namespace App\Enums;

enum PaymentScheduleStatus: string
{
    case Pending  = 'pending';
    /** @todo غير مستخدم حالياً — محجوز لمنطق التنبيه المبكر مستقبلاً */
    case NearDue  = 'near_due';
    case Due      = 'due';
    case Partial  = 'partial';
    case Paid     = 'paid';
    case Overdue  = 'overdue';
    case Cancelled = 'cancelled';
}
