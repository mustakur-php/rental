<?php

namespace Tests\Feature\Archive;

use App\Domains\Company\Models\Company;
use App\Domains\Property\Models\Property;
use App\Domains\Property\Models\PropertyLease;
use App\Domains\Unit\Models\Unit;
use App\Livewire\Archive\ArchiveIndex;
use App\Livewire\Properties\PropertyIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ArchiveWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_archiving_property_hides_it_and_archives_related_units_and_lease(): void
    {
        $this->loginAsSuperAdmin();
        [$property, $unit, $lease] = $this->createLeasedProperty();

        Livewire::test(PropertyIndex::class)
            ->call('archiveProperty', $property->id)
            ->assertHasNoErrors();

        $this->assertNotNull($property->fresh()->archived_at);
        $this->assertNotNull($unit->fresh()->archived_at);
        $this->assertNotNull($lease->fresh()->archived_at);

        Livewire::test(PropertyIndex::class)
            ->assertDontSee('Archive Test Property');
    }

    public function test_archived_property_can_be_restored_with_related_units_and_lease(): void
    {
        $this->loginAsSuperAdmin();
        [$property, $unit, $lease] = $this->createLeasedProperty();

        Livewire::test(PropertyIndex::class)
            ->call('archiveProperty', $property->id);

        Livewire::test(ArchiveIndex::class)
            ->set('tab', 'properties')
            ->assertSee('Archive Test Property')
            ->call('restore', 'property', $property->id)
            ->assertHasNoErrors();

        $this->assertNull($property->fresh()->archived_at);
        $this->assertNull($unit->fresh()->archived_at);
        $this->assertNull($lease->fresh()->archived_at);
    }

    private function createLeasedProperty(): array
    {
        $company = Company::create([
            'code' => 'ARCH-COMP',
            'name' => 'Archive Company',
            'status' => 'active',
        ]);

        $property = Property::create([
            'company_id' => $company->id,
            'code' => 'ARCH-PROP',
            'name' => 'Archive Test Property',
            'type' => 'commercial_complex',
            'ownership_type' => 'leased',
            'status' => 'active',
        ]);

        $unit = Unit::create([
            'property_id' => $property->id,
            'code' => 'ARCH-UNIT',
            'name' => 'Archive Test Unit',
            'type' => 'shop',
            'status' => 'vacant',
        ]);

        $lease = PropertyLease::create([
            'property_id' => $property->id,
            'owner_name' => 'Archive Owner',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'total_amount' => 120000,
            'payment_cycle' => 'annually',
            'installments_count' => 1,
            'status' => 'active',
        ]);

        return [$property, $unit, $lease];
    }
}
