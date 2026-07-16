<?php

namespace App\Domains\Contract\Data;

class ContractData
{
    public function __construct(
        public int $tenantId,
        public int $unitId,
        public string $startDate,
        public string $endDate,
        public string $billingCycle,
        public float $totalAmount,
        public float $vatRate = 15,
        public ?string $notes = null,
        public float $depositAmount = 0,
    ) {
    }
}
