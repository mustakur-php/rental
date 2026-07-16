@php
    $statusStr = $status instanceof \BackedEnum ? $status->value : (string) $status;

    $badgeClass = match($statusStr) {
        'rented'      => 'erp-badge erp-badge-green',
        'vacant'      => 'erp-badge erp-badge-slate',
        'maintenance' => 'erp-badge erp-badge-amber',
        'reserved'    => 'erp-badge erp-badge-blue',
        'overdue'     => 'erp-badge erp-badge-red',
        default       => 'erp-badge erp-badge-slate',
    };

    $defaultLabel = match($statusStr) {
        'rented'      => 'مؤجرة',
        'vacant'      => 'شاغرة',
        'maintenance' => 'صيانة',
        'reserved'    => 'محجوزة',
        'unavailable' => 'غير متاحة',
        default       => $statusStr,
    };

    $displayLabel = isset($label) && $label !== null && !($label instanceof \BackedEnum)
        ? $label
        : $defaultLabel;
@endphp
<span class="{{ $badgeClass }}">{{ $displayLabel }}</span>
