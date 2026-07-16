<?php

namespace App\Livewire\Properties;

use App\Domains\Company\Models\Company;
use App\Domains\Property\Models\Property;
use App\Domains\Property\Models\PropertyLease;
use App\Domains\Property\Models\PropertyLeasePeriod;
use App\Domains\Property\Models\PropertyLeaseSchedule;
use App\Traits\HasPermissionGuard;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class PropertyIndex extends Component
{
    use WithPagination, HasPermissionGuard;

    public string $search   = '';
    public string $status   = '';
    public string $viewMode = 'cards'; // cards / table

    public bool $showCreateModal = false;
    public bool $showEditModal   = false;
    public ?int  $editingPropertyId = null;

    // تصاعد إيجار العقار (مستأجر)
    public bool  $has_lease_escalation = false;
    public array $lease_periods        = [
        ['duration_months' => 12, 'increase_pct' => 0, 'annual_amount' => 0],
    ];

    public array $form = [
        'company_id'     => null,
        'code'           => '',
        'name'           => '',
        'type'           => 'commercial_complex',
        'ownership_type' => 'owned',
        'city'           => '',
        'district'       => '',
        'address'        => '',
        'description'    => '',
        'status'         => 'active',
        // بيانات عقد الإيجار (مستأجر فقط)
        'owner_name'             => '',
        'owner_mobile'           => '',
        'owner_iban'             => '',
        'lease_contract_number'  => '',
        'lease_start_date'       => '',
        'lease_end_date'         => '',
        'lease_annual_rent'      => '',   // الإيجار السنوي — الإجمالي يُحسب تلقائياً
        'lease_payment_cycle'    => 'monthly',
    ];

    public function mount(): void
    {
        $this->form['company_id']       = Company::notArchived()->active()->value('id');
        $this->form['lease_start_date'] = now()->toDateString();
        $this->form['lease_end_date']   = now()->addYear()->toDateString();
    }

    public function updatingSearch(): void { $this->resetPage(); }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = $mode;
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->editingPropertyId = null;
        $this->form = array_merge($this->form, [
            'company_id'     => Company::notArchived()->active()->value('id'),
            'code'           => $this->nextCode(),
            'name'           => '',
            'type'           => 'commercial_complex',
            'ownership_type' => 'owned',
            'city'           => '',
            'district'       => '',
            'address'        => '',
            'description'    => '',
            'status'         => 'active',
            'owner_name'             => '',
            'owner_mobile'           => '',
            'owner_iban'             => '',
            'lease_contract_number'  => '',
            'lease_start_date'       => now()->toDateString(),
            'lease_end_date'         => now()->addYear()->toDateString(),
            'lease_annual_rent'      => '',
            'lease_payment_cycle'    => 'monthly',
        ]);
        $this->has_lease_escalation = false;
        $this->lease_periods        = [['duration_months' => 12, 'increase_pct' => 0, 'annual_amount' => 0]];
        $this->showCreateModal = true;
    }

    public function createProperty(): void
    {
        if (! $this->requirePermission('properties.create')) return;
        $this->validate($this->rules());

        $property = Property::create([
            'company_id'     => $this->form['company_id'],
            'code'           => $this->form['code'],
            'name'           => $this->form['name'],
            'type'           => $this->form['type'],
            'ownership_type' => $this->form['ownership_type'],
            'city'           => $this->form['city'],
            'district'       => $this->form['district'],
            'address'        => $this->form['address'],
            'description'    => $this->form['description'],
            'status'         => $this->form['status'],
        ]);

        if ($this->form['ownership_type'] === 'leased') {
            $this->createLeaseForProperty($property);
        }

        $this->showCreateModal = false;
        $this->dispatch('notify', message: 'تم إنشاء العقار بنجاح');
    }

    public function openEditModal(int $propertyId): void
    {
        $property = Property::with('activeLease')->findOrFail($propertyId);
        $this->resetValidation();
        $this->editingPropertyId = $property->id;

        $lease = $property->activeLease;
        $this->form = [
            'company_id'     => $property->company_id,
            'code'           => $property->code,
            'name'           => $property->name,
            'type'           => $property->type,
            'ownership_type' => $property->ownership_type ?? 'owned',
            'city'           => $property->city ?? '',
            'district'       => $property->district ?? '',
            'address'        => $property->address ?? '',
            'description'    => $property->description ?? '',
            'status'         => $property->status,
            'owner_name'             => $lease?->owner_name ?? '',
            'owner_mobile'           => $lease?->owner_mobile ?? '',
            'owner_iban'             => $lease?->owner_iban ?? '',
            'lease_contract_number'  => $lease?->lease_contract_number ?? '',
            'lease_start_date'       => $lease?->start_date?->format('Y-m-d') ?? now()->toDateString(),
            'lease_end_date'         => $lease?->end_date?->format('Y-m-d') ?? now()->addYear()->toDateString(),
            'lease_annual_rent'      => $this->backCalculateAnnualRent($lease),
            'lease_payment_cycle'    => $lease?->payment_cycle ?? 'monthly',
        ];
        // تحميل فترات التصاعد إن وجدت
        if ($lease && $lease->periods()->exists()) {
            $this->has_lease_escalation = true;
            $this->lease_periods = $lease->periods->map(fn ($p) => [
                'duration_months' => $p->duration_months,
                'increase_pct'    => (float) $p->increase_percentage,
                'annual_amount'   => (float) $p->annual_amount,
            ])->toArray();
        } else {
            $this->has_lease_escalation = false;
            $this->lease_periods = [['duration_months' => 12, 'increase_pct' => 0, 'annual_amount' => (float)($this->form['lease_annual_rent'] ?? 0)]];
        }

        $this->showEditModal = true;
    }

    public function updateProperty(): void
    {
        if (! $this->requirePermission('properties.edit')) return;
        $this->validate($this->rules($this->editingPropertyId));
        $property = Property::findOrFail($this->editingPropertyId);

        $property->update([
            'company_id'     => $this->form['company_id'],
            'code'           => $this->form['code'],
            'name'           => $this->form['name'],
            'type'           => $this->form['type'],
            'ownership_type' => $this->form['ownership_type'],
            'city'           => $this->form['city'],
            'district'       => $this->form['district'],
            'address'        => $this->form['address'],
            'description'    => $this->form['description'],
            'status'         => $this->form['status'],
        ]);

        if ($this->form['ownership_type'] === 'leased') {
            if ($this->has_lease_escalation) {
                $this->recomputeLeasePeriodAmounts();
            }

            $existing = $property->activeLease;
            if ($existing) {
                $existing->update([
                    'owner_name'            => $this->form['owner_name'],
                    'owner_mobile'          => $this->form['owner_mobile'],
                    'owner_iban'            => $this->form['owner_iban'],
                    'lease_contract_number' => $this->form['lease_contract_number'],
                    'start_date'            => $this->form['lease_start_date'],
                    'end_date'              => $this->form['lease_end_date'],
                    'total_amount'          => $this->calcLeaseTotalAmount(),
                    'payment_cycle'         => $this->form['lease_payment_cycle'],
                ]);

                if ($this->has_lease_escalation) {
                    $this->savePropertyLeasePeriods($existing);
                } else {
                    $existing->periods()->delete();
                }

                $this->syncLeaseSchedules($existing);
            } else {
                $this->createLeaseForProperty($property);
            }
        }

        $this->showEditModal = false;
        $this->dispatch('notify', message: 'تم تحديث العقار بنجاح');
    }

    public function archiveProperty(int $propertyId): void
    {
        if (! $this->requirePermission('properties.archive')) return;
        $property = Property::with(['units', 'activeLease'])->findOrFail($propertyId);

        $property->update([
            'archived_at' => now(),
            'archived_reason' => 'archived_from_properties',
        ]);

        $property->units()->update([
            'archived_at' => now(),
            'archived_reason' => 'property_archived',
        ]);

        $property->activeLease?->update([
            'archived_at' => now(),
            'archived_reason' => 'property_archived',
        ]);

        $this->dispatch('notify', message: 'تم نقل العقار ووحداته إلى الأرشيف');
    }

    // ─── تصاعد إيجار العقار ──────────────────────────
    public function addLeasePeriod(): void
    {
        $this->lease_periods[] = ['duration_months' => 12, 'increase_pct' => 0, 'annual_amount' => 0];
        $this->recomputeLeasePeriodAmounts();
    }

    public function removeLeasePeriod(int $index): void
    {
        if (count($this->lease_periods) <= 1) return;
        array_splice($this->lease_periods, $index, 1);
        $this->lease_periods = array_values($this->lease_periods);
        $this->recomputeLeasePeriodAmounts();
    }

    public function updatedLeasePeriods(): void
    {
        $this->recomputeLeasePeriodAmounts();
    }

    public function updatedHasLeaseEscalation(): void
    {
        if ($this->has_lease_escalation) {
            $base = (float) ($this->form['lease_annual_rent'] ?? 0);
            $this->lease_periods = [['duration_months' => 12, 'increase_pct' => 0, 'annual_amount' => $base]];
        }
    }

    public function updatedFormLeaseAnnualRent(): void
    {
        $this->recomputeLeasePeriodAmounts();
    }

    private function recomputeLeasePeriodAmounts(): void
    {
        if (! $this->has_lease_escalation) return;
        $base = (float) ($this->form['lease_annual_rent'] ?? 0);
        foreach ($this->lease_periods as $i => &$period) {
            if ($i === 0) {
                $period['annual_amount'] = $base;
            } else {
                $prev  = $this->lease_periods[$i - 1];
                $pct   = (float) ($period['increase_pct'] ?? 0);
                $period['annual_amount'] = round((float) $prev['annual_amount'] * (1 + $pct / 100), 2);
            }
        }
        unset($period);
    }

    private function savePropertyLeasePeriods(PropertyLease $lease): void
    {
        $lease->periods()->delete();
        foreach ($this->lease_periods as $i => $period) {
            PropertyLeasePeriod::create([
                'property_lease_id'   => $lease->id,
                'period_no'           => $i + 1,
                'duration_months'     => (int) $period['duration_months'],
                'annual_amount'       => (float) $period['annual_amount'],
                'increase_percentage' => (float) ($period['increase_pct'] ?? 0),
            ]);
        }
    }

    protected function createLeaseForProperty(Property $property): void
    {
        if ($this->has_lease_escalation) {
            $this->recomputeLeasePeriodAmounts();
        }

        $cycle        = $this->form['lease_payment_cycle'];
        $totalAmount  = $this->calcLeaseTotalAmount();
        $installments = $this->calcLeaseInstallmentsCount();

        $lease = PropertyLease::create([
            'property_id'           => $property->id,
            'owner_name'            => $this->form['owner_name'],
            'owner_mobile'          => $this->form['owner_mobile'],
            'owner_iban'            => $this->form['owner_iban'],
            'lease_contract_number' => $this->form['lease_contract_number'],
            'start_date'            => $this->form['lease_start_date'],
            'end_date'              => $this->form['lease_end_date'],
            'total_amount'          => $totalAmount,
            'payment_cycle'         => $cycle,
            'installments_count'    => $installments,
            'status'                => 'active',
        ]);

        if ($this->has_lease_escalation) {
            $this->savePropertyLeasePeriods($lease);
        }

        $this->syncLeaseSchedules($lease);
    }

    protected function syncLeaseSchedules(PropertyLease $lease): void
    {
        $hasPaidSchedules = $lease->schedules()
            ->where(fn ($q) => $q->where('paid_amount', '>', 0)->orWhereIn('status', ['paid', 'partial']))
            ->exists();

        if ($hasPaidSchedules) {
            return;
        }

        $lease->schedules()->delete();
        $monthStep = $this->leaseCycleMonths($lease->payment_cycle);

        $periods = $lease->periods()->orderBy('period_no')->get();

        if ($periods->isNotEmpty()) {
            // توليد جدول بالفترات المتصاعدة
            $installmentNo   = 1;
            $periodStartDate = Carbon::parse($lease->start_date);

            foreach ($periods as $period) {
                $durationMonths     = (int) $period->duration_months;
                $annualAmount       = (float) $period->annual_amount;
                $periodTotal        = round($annualAmount * ($durationMonths / 12), 2);
                $periodInstallments = max(1, (int) floor($durationMonths / $monthStep));
                $baseAmount         = round($periodTotal / $periodInstallments, 2);

                for ($i = 1; $i <= $periodInstallments; $i++) {
                    $amount = $i === $periodInstallments
                        ? round($periodTotal - ($baseAmount * ($periodInstallments - 1)), 2)
                        : $baseAmount;

                    PropertyLeaseSchedule::create([
                        'property_lease_id' => $lease->id,
                        'installment_no'    => $installmentNo++,
                        'due_date'          => $periodStartDate->copy()->addMonthsNoOverflow(($i - 1) * $monthStep)->toDateString(),
                        'amount'            => $amount,
                        'paid_amount'       => 0,
                        'remaining_amount'  => $amount,
                        'status'            => 'pending',
                    ]);
                }

                $periodStartDate->addMonthsNoOverflow($durationMonths);
            }

            $lease->update(['installments_count' => $installmentNo - 1]);
        } else {
            // بدون تصاعد — الطريقة الأصلية
            $totalAmount  = round((float) $lease->total_amount, 2);
            $installments = $this->calculateLeaseInstallmentsCount($lease->start_date, $lease->end_date, $lease->payment_cycle);
            $baseAmount   = round($totalAmount / $installments, 2);
            $startDate    = Carbon::parse($lease->start_date);

            $lease->update(['installments_count' => $installments]);

            for ($i = 1; $i <= $installments; $i++) {
                $amount = $i === $installments
                    ? round($totalAmount - ($baseAmount * ($installments - 1)), 2)
                    : $baseAmount;

                PropertyLeaseSchedule::create([
                    'property_lease_id' => $lease->id,
                    'installment_no'    => $i,
                    'due_date'          => $startDate->copy()->addMonthsNoOverflow(($i - 1) * $monthStep)->toDateString(),
                    'amount'            => $amount,
                    'paid_amount'       => 0,
                    'remaining_amount'  => $amount,
                    'status'            => 'pending',
                ]);
            }
        }
    }

    protected function calculateLeaseInstallmentsCount(string|\DateTimeInterface $startDate, string|\DateTimeInterface $endDate, string $cycle): int
    {
        $start     = Carbon::parse($startDate)->startOfDay();
        $end       = Carbon::parse($endDate)->startOfDay();
        $monthStep = $this->leaseCycleMonths($cycle);

        $totalMonths = (int) $start->diffInMonths($end->copy()->addDay());
        return max(1, (int) floor($totalMonths / $monthStep));
    }

    protected function leaseCycleMonths(string $cycle): int
    {
        return match ($cycle) {
            'monthly' => 1,
            'two_months', 'bimonthly' => 2,
            'quarterly' => 3,
            'semi_annually', 'semi_annual' => 6,
            'annually', 'annual' => 12,
            default => 1,
        };
    }

    protected function rules(?int $ignoreId = null): array
    {
        $uniqueCode = 'unique:properties,code' . ($ignoreId ? ','.$ignoreId : '');
        $isLeased   = ($this->form['ownership_type'] ?? 'owned') === 'leased';

        return [
            'form.company_id'     => ['required', 'exists:companies,id'],
            'form.code'           => ['required', 'string', 'max:50', $uniqueCode],
            'form.name'           => ['required', 'string', 'max:255'],
            'form.type'           => ['required', 'string', 'max:50'],
            'form.ownership_type' => ['required', 'in:owned,leased'],
            'form.city'           => ['nullable', 'string', 'max:100'],
            'form.district'       => ['nullable', 'string', 'max:100'],
            'form.address'        => ['nullable', 'string', 'max:255'],
            'form.description'    => ['nullable', 'string'],
            'form.status'         => ['required', 'string', 'max:30'],
            // بيانات عقد الإيجار (إلزامية فقط إذا مستأجر)
            'form.owner_name'            => [$isLeased ? 'required' : 'nullable', 'string', 'max:255'],
            'form.owner_mobile'          => ['nullable', 'string', 'max:50'],
            'form.owner_iban'            => ['nullable', 'string', 'max:50'],
            'form.lease_contract_number' => ['nullable', 'string', 'max:100'],
            'form.lease_start_date'      => [$isLeased ? 'required' : 'nullable', 'date'],
            'form.lease_end_date'        => [$isLeased ? 'required' : 'nullable', 'date', 'after:form.lease_start_date'],
            'form.lease_annual_rent'     => [$isLeased ? 'required' : 'nullable', 'numeric', 'min:1'],
            'form.lease_payment_cycle'   => ['required', 'string'],
        ];
    }

    // ─── حسابات عقد المالك (private — تُستدعى من render وcreateLeaseForProperty) ──
    private function calcLeaseDurationMonths(): int
    {
        $form  = isset($this->form) ? $this->form : [];
        $start = $form['lease_start_date'] ?? '';
        $end   = $form['lease_end_date']   ?? '';
        if (! $start || ! $end) return 0;
        $s = Carbon::parse($start)->startOfDay();
        $e = Carbon::parse($end)->startOfDay();
        if ($e->lte($s)) return 0;
        return (int) $s->diffInMonths($e->copy()->addDay());
    }

    private function calcLeaseTotalAmount(): float
    {
        if ($this->has_lease_escalation && ! empty($this->lease_periods)) {
            $total = 0.0;
            foreach ($this->lease_periods as $p) {
                $total += (float) ($p['annual_amount'] ?? 0) * ((int) ($p['duration_months'] ?? 0) / 12);
            }
            return round($total, 2);
        }
        $form       = isset($this->form) ? $this->form : [];
        $annualRent = (float) ($form['lease_annual_rent'] ?? 0);
        $months     = $this->calcLeaseDurationMonths();
        if (! $annualRent || ! $months) return 0.0;
        return round($annualRent * ($months / 12), 2);
    }

    private function calcLeaseInstallmentsCount(): int
    {
        $form = isset($this->form) ? $this->form : [];
        $step = $this->leaseCycleMonths($form['lease_payment_cycle'] ?? 'monthly');

        if ($this->has_lease_escalation && ! empty($this->lease_periods)) {
            $count = 0;
            foreach ($this->lease_periods as $p) {
                $months  = (int) ($p['duration_months'] ?? 0);
                $count  += max(1, (int) floor($months / $step));
            }
            return $count;
        }

        $months = $this->calcLeaseDurationMonths();
        if (! $months) return 0;
        return max(1, (int) floor($months / $step));
    }

    private function calcLeaseInstallmentAmount(): float
    {
        $total = $this->calcLeaseTotalAmount();
        $count = $this->calcLeaseInstallmentsCount();
        if (! $count || ! $total) return 0.0;
        return round($total / $count, 2);
    }

    // إعادة حساب الإيجار السنوي من إجمالي مخزون (عند التعديل)
    protected function backCalculateAnnualRent(?PropertyLease $lease): float
    {
        if (! $lease || ! $lease->total_amount) return 0.0;
        $months = (int) Carbon::parse($lease->start_date)
            ->diffInMonths(Carbon::parse($lease->end_date)->addDay());
        if (! $months) return (float) $lease->total_amount;
        return round((float)$lease->total_amount * 12 / $months, 2);
    }

    protected function nextCode(): string
    {
        $next = (Property::max('id') ?? 0) + 1;
        return 'PROP-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function render()
    {
        $properties = Property::query()
            ->notArchived()
            ->withCount([
                'units as units_count' => fn ($q) => $q->notArchived(),
                'units as rented_units_count' => fn ($q) => $q->notArchived()->where('status', 'rented'),
                'units as vacant_units_count'  => fn ($q) => $q->notArchived()->where('status', 'vacant'),
            ])
            ->with('activeLease')
            ->when($this->search, function ($q) {
                $q->where(function ($q) {
                    $q->where('name',     'like', '%'.$this->search.'%')
                      ->orWhere('code',   'like', '%'.$this->search.'%')
                      ->orWhere('city',   'like', '%'.$this->search.'%')
                      ->orWhere('district','like', '%'.$this->search.'%');
                });
            })
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->latest()
            ->paginate(12);

        $companies = Company::notArchived()->active()->orderBy('name')->get(['id', 'name', 'code']);

        $leaseDurationMonths          = $this->calcLeaseDurationMonths();
        $computedLeaseTotalAmount     = $this->calcLeaseTotalAmount();
        $leaseInstallmentsPreviewCount  = $this->calcLeaseInstallmentsCount();
        $leaseInstallmentsPreviewAmount = $this->calcLeaseInstallmentAmount();

        return view('livewire.properties.property-index',
            compact('properties', 'companies', 'leaseDurationMonths',
                    'computedLeaseTotalAmount', 'leaseInstallmentsPreviewCount',
                    'leaseInstallmentsPreviewAmount'))
            ->layout('layouts.app', ['title' => 'العقارات']);
    }
}
