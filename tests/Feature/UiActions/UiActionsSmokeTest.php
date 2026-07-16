<?php

namespace Tests\Feature\UiActions;

use App\Domains\Company\Models\Company;
use App\Domains\Contract\Actions\CreateContractAction;
use App\Domains\Contract\Data\ContractData;
use App\Domains\Maintenance\Models\MaintenanceRequest;
use App\Domains\Property\Models\Property;
use App\Domains\Property\Models\PropertyLease;
use App\Domains\Property\Models\PropertyLeaseSchedule;
use App\Domains\Tenant\Models\Tenant;
use App\Domains\Unit\Models\Unit;
use App\Livewire\Maintenance\MaintenanceIndex;
use App\Livewire\Payments\LeaseSchedulesIndex;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UiActionsSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_navigation_pages_render(): void
    {
        $user = $this->loginAsSuperAdmin();
        $property = $this->createProperty();
        $unit = Unit::create([
            'property_id' => $property->id,
            'code' => 'UNIT-NAV',
            'name' => 'Navigation Unit',
            'type' => 'apartment',
            'status' => 'vacant',
        ]);
        $vacantUnit = Unit::create([
            'property_id' => $property->id,
            'code' => 'UNIT-NAV-VACANT',
            'name' => 'Navigation Vacant Unit',
            'type' => 'apartment',
            'status' => 'vacant',
        ]);
        $tenant = Tenant::create([
            'code' => 'TEN-NAV',
            'type' => 'individual',
            'name' => 'Navigation Tenant',
            'status' => 'active',
        ]);
        $contract = app(CreateContractAction::class)->execute(new ContractData(
            tenantId: $tenant->id,
            unitId: $unit->id,
            startDate: '2026-01-01',
            endDate: '2026-12-31',
            billingCycle: 'quarterly',
            totalAmount: 120000,
            vatRate: 15,
        ));

        $routes = [
            route('dashboard'),
            route('properties.index'),
            route('properties.show', $property),
            route('units.index'),
            route('units.show', $unit),
            route('tenants.index'),
            route('contracts.index'),
            route('contracts.create'),
            route('contracts.create', ['unit_id' => $vacantUnit->id]),
            route('contracts.schedule', $contract),
            route('maintenance.index'),
            route('payments.tenants'),
            route('payments.leases'),
            route('notifications.index'),
            route('reports.index'),
            route('archive.index'),
        ];

        foreach ($routes as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }
    }

    public function test_maintenance_request_can_be_created_without_optional_cost_or_unit(): void
    {
        $this->loginAsSuperAdmin();
        $property = $this->createProperty();

        Livewire::test(MaintenanceIndex::class)
            ->set('form.property_id', $property->id)
            ->set('form.unit_id', '')
            ->set('form.title', 'Air conditioning check')
            ->set('form.cost', '')
            ->set('form.request_date', '2026-05-23')
            ->call('createRequest')
            ->assertHasNoErrors();

        $request = MaintenanceRequest::where('title', 'Air conditioning check')->firstOrFail();

        $this->assertNull($request->unit_id);
        $this->assertSame(0.0, (float) $request->cost);
    }

    public function test_owner_payment_over_remaining_keeps_modal_open_and_shows_error(): void
    {
        $this->loginAsSuperAdmin();
        $property = $this->createProperty(['ownership_type' => 'leased']);
        $lease = PropertyLease::create([
            'property_id' => $property->id,
            'owner_name' => 'Owner',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'total_amount' => 120000,
            'payment_cycle' => 'annually',
            'installments_count' => 1,
            'status' => 'active',
        ]);
        $schedule = PropertyLeaseSchedule::create([
            'property_lease_id' => $lease->id,
            'installment_no' => 1,
            'due_date' => '2026-12-01',
            'amount' => 120000,
            'paid_amount' => 0,
            'remaining_amount' => 120000,
            'status' => 'pending',
        ]);

        Livewire::test(LeaseSchedulesIndex::class)
            ->call('openPaymentModal', $schedule->id)
            ->set('paymentForm.amount', 130000)
            ->set('paymentForm.reference_number', 'REF-TEST-001')
            ->call('registerPayment')
            ->assertHasErrors(['paymentForm.amount'])
            ->assertSet('showPaymentModal', true);

        $this->assertSame(120000.0, (float) $schedule->fresh()->remaining_amount);
        $this->assertSame('pending', $schedule->fresh()->status);
    }

    private function createProperty(array $attributes = []): Property
    {
        $company = Company::create([
            'code' => 'COMP-UI',
            'name' => 'UI Company',
            'status' => 'active',
        ]);

        return Property::create(array_merge([
            'company_id' => $company->id,
            'code' => 'PROP-UI-' . random_int(1000, 9999),
            'name' => 'UI Property',
            'type' => 'residential',
            'ownership_type' => 'owned',
            'status' => 'active',
        ], $attributes));
    }
}
