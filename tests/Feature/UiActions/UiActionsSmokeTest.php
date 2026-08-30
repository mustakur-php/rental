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
use App\Livewire\Properties\PropertyIndex;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_creating_leased_property_dispatches_lease_created_event(): void
    {
        $this->loginAsSuperAdmin();
        $company = Company::create([
            'code' => 'COMP-LEASE',
            'name' => 'Lease Company',
            'status' => 'active',
        ]);

        Livewire::test(PropertyIndex::class)
            ->set('form.company_id', $company->id)
            ->set('form.code', 'PROP-LEASE-UPLOAD')
            ->set('form.name', 'Leased Upload Property')
            ->set('form.type', 'commercial_complex')
            ->set('form.ownership_type', 'leased')
            ->set('form.status', 'active')
            ->set('form.owner_name', 'Owner Upload')
            ->set('form.lease_start_date', '2026-01-01')
            ->set('form.lease_end_date', '2026-12-31')
            ->set('form.lease_annual_rent', 120000)
            ->set('form.lease_payment_cycle', 'annually')
            ->call('createProperty')
            ->assertHasNoErrors()
            ->assertDispatched('lease-created');
    }

    public function test_lease_contract_upload_saves_file_to_contract_file_path(): void
    {
        Storage::fake('public');
        $this->loginAsSuperAdmin();

        $property = $this->createProperty(['ownership_type' => 'owned']);
        $lease = PropertyLease::create([
            'property_id'        => $property->id,
            'owner_name'         => 'Test Owner',
            'start_date'         => '2026-01-01',
            'end_date'           => '2026-12-31',
            'total_amount'       => 120000,
            'vat_rate'           => 0,
            'vat_amount'         => 0,
            'total_with_vat'     => 120000,
            'payment_cycle'      => 'annually',
            'installments_count' => 1,
            'status'             => 'active',
        ]);

        Livewire::test(\App\Livewire\Properties\LeaseContractUpload::class, [
            'leaseId'     => $lease->id,
            'currentPath' => null,
        ])
            ->set('file', UploadedFile::fake()->create('owner-contract.pdf', 120, 'application/pdf'))
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('notify');

        $this->assertNotNull($lease->fresh()->contract_file_path);
        $this->assertStringStartsWith('owner-contracts/', $lease->fresh()->contract_file_path);
        Storage::disk('public')->assertExists($lease->fresh()->contract_file_path);
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
