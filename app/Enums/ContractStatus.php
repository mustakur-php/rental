<?php

namespace App\Enums;

enum ContractStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Ended = 'ended';
    case EarlyEnded = 'early_ended';
    case Cancelled = 'cancelled';
    case Renewed = 'renewed';
}
