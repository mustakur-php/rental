<?php

namespace Tests\Feature\Properties;

use App\Domains\Company\Models\Company;
use App\Domains\Property\Models\Property;
use App\Livewire\Properties\PropertyIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PropertyLeaseScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_leased_property_generates_owner_schedules_for_full_lease_duration(): void
    {
        $this->loginAsSuperAdmin();

        $company = Company::create([
            'code' => 'COMP-LEASE',
            'name' => 'Lease Company',
            'status' => 'active',
        ]);

        Livewire::test(PropertyIndex::class)
            ->set('form.company_id', $company->id)
            ->set('form.code', 'PROP-LEASE-001')
            ->set('form.name', 'Leased Property')
            ->set('form.type', 'commercial_complex')
            ->set('form.ownership_type', 'leased')
            ->set('form.status', 'active')
            ->set('form.owner_name', 'Owner Name')
            ->set('form.lease_start_date', '2025-01-01')
            ->set('form.lease_end_date', '2027-12-31')
            ->set('form.lease_annual_rent', 100000)    // 3 سنوات × 100,000 = 300,000 إجمالي
            ->set('form.lease_payment_cycle', 'semi_annually')
            ->call('createProperty')
            ->assertHasNoErrors();

        $property = Property::where('code', 'PROP-LEASE-001')->with('activeLease.schedules')->firstOrFail();

        $this->assertEquals(6, $property->activeLease->installments_count);
        $this->assertCount(6, $property->activeLease->schedules);
        $this->assertEquals(300000.0, round((float) $property->activeLease->schedules->sum('amount'), 2));
        $this->assertEquals([
            '2025-01-01',
            '2025-07-01',
            '2026-01-01',
            '2026-07-01',
            '2027-01-01',
            '2027-07-01',
        ], $property->activeLease->schedules->sortBy('installment_no')->pluck('due_date')->map->format('Y-m-d')->values()->all());
    }
}
