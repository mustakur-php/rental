<?php

namespace App\Enums;

enum ContractStatus: string
{
    /** @todo غير مستخدم — العقود تُنشأ مباشرة بحالة Active */
    case Draft     = 'draft';
    case Active    = 'active';
    case Ended     = 'ended';
    case EarlyEnded = 'early_ended';
    case Cancelled = 'cancelled';
    /** @todo غير مستخدم — محجوز لسير عمل التجديد مستقبلاً (previous_contract_id جاهز) */
    case Renewed   = 'renewed';
}
