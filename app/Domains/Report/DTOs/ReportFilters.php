<?php

namespace App\Domains\Report\DTOs;

class ReportFilters
{
    public function __construct(
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public ?int $propertyId = null,
        public ?int $unitId = null,
        public ?int $tenantId = null,
        public ?string $contractStatus = null,
        public ?string $unitStatus = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            dateFrom: $data['date_from'] ?? null,
            dateTo: $data['date_to'] ?? null,
            propertyId: isset($data['property_id']) && $data['property_id'] !== '' ? (int) $data['property_id'] : null,
            unitId: isset($data['unit_id']) && $data['unit_id'] !== '' ? (int) $data['unit_id'] : null,
            tenantId: isset($data['tenant_id']) && $data['tenant_id'] !== '' ? (int) $data['tenant_id'] : null,
            contractStatus: $data['contract_status'] ?? null,
            unitStatus: $data['unit_status'] ?? null,
        );
    }
}
