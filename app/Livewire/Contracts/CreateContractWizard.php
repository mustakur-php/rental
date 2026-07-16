<?php

namespace App\Livewire\Contracts;

use App\Traits\HasPermissionGuard;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Domains\Contract\Actions\CreateContractAction;
use App\Domains\Contract\Data\ContractData;
use App\Domains\Tenant\Models\Tenant;
use App\Domains\Unit\Models\Unit;

class CreateContractWizard extends Component
{
    use WithFileUploads, HasPermissionGuard;

    public int $step = 1;

    // الخطوة 1 — المستأجر
    public ?int    $tenant_id    = null;
    public string  $tenantSearch = '';

    // الخطوة 2 — الوحدة
    public ?int    $unit_id      = null;
    public string  $unitSearch   = '';

    // الخطوة 3 — بيانات العقد
    public ?string $ejar_number    = null;
    public ?string $start_date     = null;
    public ?string $end_date       = null;
    public string  $billing_cycle  = 'monthly';
    public float   $annual_rent    = 0;
    public float   $vat_rate       = 15;
    public ?string $notes          = null;
    public $contract_file          = null;

    // تصاعد الإيجار
    public bool  $has_escalation = false;
    public array $periods        = [
        ['duration_months' => 12, 'increase_pct' => 0],
    ];

    public function mount(): void
    {
        $unitId = request()->integer('unit_id');
        if ($unitId) {
            $this->unit_id = $unitId;
        }
    }

    // ─── حوادث wire:model.live ───────────────────────
    // تُستدعى تلقائياً عند تغيير قيمة الـ radio من المتصفح
    // لا تُقدّم الخطوة — زر التالي هو الوحيد الذي يُقدّم
    public function updatedTenantId(): void
    {
        $this->resetValidation('tenant_id');
    }

    public function updatedUnitId(): void
    {
        $this->resetValidation('unit_id');
    }

    // ─── التنقل بين الخطوات ──────────────────────────
    public function nextStep(): void
    {
        match ($this->step) {
            1 => $this->validate(
                ['tenant_id' => ['required', 'integer', 'exists:tenants,id']],
                ['tenant_id.required' => 'يجب اختيار مستأجر للمتابعة']
            ),
            2 => $this->validate(
                ['unit_id' => ['required', 'integer', 'exists:units,id']],
                ['unit_id.required' => 'يجب اختيار وحدة شاغرة للمتابعة']
            ),
            3 => $this->validateStep3(),
            default => null,
        };

        $this->step = min(4, $this->step + 1);
    }

    public function previousStep(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    // ─── يُستخدم من الاختبارات مباشرةً ──────────────
    public function selectTenant(int $id): void
    {
        $this->tenant_id = $id;
        $this->resetValidation('tenant_id');
    }

    public function selectUnit(int $id): void
    {
        $this->unit_id = $id;
        $this->resetValidation('unit_id');
    }

    // ─── تصاعد الإيجار — إدارة الفترات ──────────────
    public function addPeriod(): void
    {
        $this->periods[] = ['duration_months' => 12, 'increase_pct' => 0, 'annual_amount' => 0];
        $this->recomputePeriodAmounts();
    }

    public function removePeriod(int $index): void
    {
        if (count($this->periods) <= 1) return;
        array_splice($this->periods, $index, 1);
        $this->periods = array_values($this->periods);
        $this->recomputePeriodAmounts();
    }

    public function updatedPeriods(): void
    {
        $this->recomputePeriodAmounts();
    }

    public function updatedAnnualRent(): void
    {
        $this->recomputePeriodAmounts();
    }

    public function updatedHasEscalation(): void
    {
        if ($this->has_escalation) {
            $this->periods = [['duration_months' => 12, 'increase_pct' => 0, 'annual_amount' => $this->annual_rent]];
        }
    }

    private function recomputePeriodAmounts(): void
    {
        if (! $this->has_escalation) return;
        $base = (float) $this->annual_rent;
        foreach ($this->periods as $i => &$period) {
            if ($i === 0) {
                $period['annual_amount'] = $base;
            } else {
                $prev  = $this->periods[$i - 1];
                $pct   = (float) ($period['increase_pct'] ?? 0);
                $period['annual_amount'] = round((float) $prev['annual_amount'] * (1 + $pct / 100), 2);
            }
        }
        unset($period);
    }

    // ─── التحقق من الخطوة 3 ──────────────────────────
    protected function validateStep3(): void
    {
        $rules = [
            'ejar_number'   => ['required', 'string', 'max:100'],
            'start_date'    => ['required', 'date'],
            'end_date'      => ['required', 'date', 'after:start_date'],
            'billing_cycle' => ['required', 'string'],
            'annual_rent'   => ['required', 'numeric', 'min:1'],
            'vat_rate'      => ['required', 'numeric', 'min:0'],
            'contract_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];

        if ($this->has_escalation) {
            $rules['periods']                       = ['required', 'array', 'min:1'];
            $rules['periods.*.duration_months']     = ['required', 'integer', 'min:1'];
            $rules['periods.*.increase_pct']        = ['required', 'numeric', 'min:0'];
        }

        $this->validate($rules, [
            'ejar_number.required'          => 'رقم عقد إيجار إلزامي',
            'start_date.required'           => 'تاريخ البداية إلزامي',
            'end_date.required'             => 'تاريخ النهاية إلزامي',
            'end_date.after'                => 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية',
            'annual_rent.required'          => 'قيمة الإيجار السنوي الأساسي إلزامية',
            'annual_rent.min'               => 'قيمة الإيجار السنوي يجب أن تكون أكبر من صفر',
            'contract_file.required'        => 'يجب رفع نسخة من العقد للمتابعة',
            'contract_file.mimes'           => 'يجب أن يكون الملف PDF أو صورة',
            'contract_file.max'             => 'حجم الملف لا يتجاوز 10MB',
            'periods.*.duration_months.min' => 'مدة الفترة يجب أن تكون شهراً على الأقل',
            'periods.*.increase_pct.min'    => 'نسبة الزيادة لا يمكن أن تكون سالبة',
        ]);

        if ($this->has_escalation) {
            $contractMonths = $this->calcDurationMonths();
            $periodsMonths  = array_sum(array_column($this->periods, 'duration_months'));
            if ($periodsMonths !== $contractMonths) {
                $this->addError('periods', "مجموع مدد الفترات ({$periodsMonths} شهر) يجب أن يساوي مدة العقد ({$contractMonths} شهر)");
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'periods' => "مجموع مدد الفترات ({$periodsMonths} شهر) يجب أن يساوي مدة العقد ({$contractMonths} شهر)",
                ]);
            }
        }
    }

    // ─── إنشاء العقد ─────────────────────────────────
    public function createContract(CreateContractAction $action): void
    {
        if (! $this->requirePermission('contracts.create')) return;
        $this->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'unit_id'   => ['required', 'integer', 'exists:units,id'],
        ]);

        $this->validateStep3();

        if ($this->has_escalation) {
            $this->recomputePeriodAmounts();
        }

        $filePath = null;
        if ($this->contract_file) {
            $filePath = $this->contract_file->store('contracts', 'public');
        }

        $periodsForAction = [];
        if ($this->has_escalation) {
            foreach ($this->periods as $p) {
                $periodsForAction[] = [
                    'duration_months'     => (int) $p['duration_months'],
                    'annual_amount'       => (float) $p['annual_amount'],
                    'increase_percentage' => (float) ($p['increase_pct'] ?? 0),
                ];
            }
        }

        $data = new ContractData(
            tenantId:     $this->tenant_id,
            unitId:       $this->unit_id,
            startDate:    $this->start_date,
            endDate:      $this->end_date,
            billingCycle: $this->billing_cycle,
            totalAmount:  $this->calcTotalAmount(),
            vatRate:      $this->vat_rate,
            notes:        $this->notes,
            periods:      $periodsForAction,
        );

        $contract = $action->execute($data);

        $contract->update([
            'ejar_number'        => $this->ejar_number,
            'contract_file_path' => $filePath,
        ]);

        session()->flash('success', 'تم إنشاء العقد وتوليد الاستحقاقات بنجاح.');
        $this->redirectRoute('contracts.schedule', $contract);
    }

    // ─── حسابات العقد (private — تُستدعى من render وcreateContract) ──
    // نستخدم isset() لأن Livewire 4 يمسح الـ properties أثناء hydration
    // وإذا لم تكن الـ property في الـ stored state (مثلاً state قديم من المتصفح)
    // فستبقى غير موجودة ويُسبّب الوصول المباشر إليها PropertyNotFoundException

    private function calcDurationMonths(): int
    {
        $start = isset($this->start_date) ? $this->start_date : null;
        $end   = isset($this->end_date)   ? $this->end_date   : null;
        if (! $start || ! $end) return 0;
        $s = Carbon::parse($start)->startOfDay();
        $e = Carbon::parse($end)->startOfDay();
        if ($e->lte($s)) return 0;
        return (int) $s->diffInMonths($e->copy()->addDay());
    }

    private function calcTotalAmount(): float
    {
        if ($this->has_escalation && ! empty($this->periods)) {
            $total = 0.0;
            foreach ($this->periods as $p) {
                $total += (float) ($p['annual_amount'] ?? 0) * ((int) ($p['duration_months'] ?? 0) / 12);
            }
            return round($total, 2);
        }
        $annualRent = isset($this->annual_rent) ? (float) $this->annual_rent : 0.0;
        $months     = $this->calcDurationMonths();
        if (! $annualRent || ! $months) return 0.0;
        return round($annualRent * ($months / 12), 2);
    }

    private function calcInstallmentsCount(): int
    {
        $cycle = isset($this->billing_cycle) ? $this->billing_cycle : 'monthly';
        $step  = match ($cycle) {
            'monthly'       => 1,
            'two_months'    => 2,
            'quarterly'     => 3,
            'semi_annually' => 6,
            'annually'      => 12,
            default         => 1,
        };

        if ($this->has_escalation && ! empty($this->periods)) {
            $count = 0;
            foreach ($this->periods as $p) {
                $months  = (int) ($p['duration_months'] ?? 0);
                $count  += max(1, (int) floor($months / $step));
            }
            return $count;
        }

        $months = $this->calcDurationMonths();
        if (! $months) return 0;
        return max(1, (int) floor($months / $step));
    }

    private function calcInstallmentAmount(): float
    {
        $total   = $this->calcTotalAmount();
        $count   = $this->calcInstallmentsCount();
        $vatRate = isset($this->vat_rate) ? (float) $this->vat_rate : 15.0;
        if (! $count || ! $total) return 0.0;
        return round($total * (1 + $vatRate / 100) / $count, 2);
    }

    // ─── Computed Properties ──────────────────────────
    #[Computed]
    public function tenants()
    {
        return Tenant::query()
            ->notArchived()
            ->where('status', 'active')
            ->when($this->tenantSearch, fn ($q) => $q->where(function ($q) {
                $q->where('name',         'like', '%'.$this->tenantSearch.'%')
                  ->orWhere('national_id', 'like', '%'.$this->tenantSearch.'%')
                  ->orWhere('code',        'like', '%'.$this->tenantSearch.'%');
            }))
            ->limit(10)->get();
    }

    #[Computed]
    public function vacantUnits()
    {
        return Unit::query()
            ->with('property')
            ->notArchived()
            ->whereHas('property', fn ($q) => $q->notArchived())
            ->where('status', 'vacant')
            ->when($this->unitSearch, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', '%'.$this->unitSearch.'%')
                  ->orWhere('code', 'like', '%'.$this->unitSearch.'%')
                  ->orWhereHas('property', fn ($q) => $q->where('name', 'like', '%'.$this->unitSearch.'%'));
            }))
            ->limit(10)->get();
    }

    #[Computed]
    public function selectedTenant()
    {
        return $this->tenant_id
            ? Tenant::notArchived()->find($this->tenant_id)
            : null;
    }

    #[Computed]
    public function selectedUnit()
    {
        return $this->unit_id
            ? Unit::query()->notArchived()->with('property')->find($this->unit_id)
            : null;
    }

    public function render()
    {
        $contractDurationMonths    = $this->calcDurationMonths();
        $calculatedTotalAmount     = $this->calcTotalAmount();
        $installmentsPreviewCount  = $this->calcInstallmentsCount();
        $installmentsPreviewAmount = $this->calcInstallmentAmount();

        return view('livewire.contracts.create-contract-wizard',
            compact('contractDurationMonths', 'calculatedTotalAmount',
                    'installmentsPreviewCount', 'installmentsPreviewAmount'))
            ->layout('layouts.app', ['title' => 'إنشاء عقد']);
    }
}
