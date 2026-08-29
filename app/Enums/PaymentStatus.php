<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Registered = 'registered';
    /** @todo عند الإلغاء يجب تحديث paid_amount/remaining_amount على الجدول المرتبط */
    case Cancelled  = 'cancelled';
    /** @todo غير مستخدم — محجوز لحالة استرداد الدفعة مستقبلاً */
    case Returned   = 'returned';
}
