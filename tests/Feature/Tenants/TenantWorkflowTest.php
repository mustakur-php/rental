<?php

namespace Tests\Feature\Tenants;

use App\Domains\Tenant\Models\Tenant;
use App\Livewire\Tenants\TenantIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TenantWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_individual_tenant_can_be_created_with_empty_optional_fields(): void
    {
        $this->loginAsSuperAdmin();

        Livewire::test(TenantIndex::class)
            ->set('form.type', 'individual')
            ->set('form.name', 'Ahmed Tenant')
            ->set('form.mobile', '0577959443')
            ->set('form.email', '')
            ->set('form.address', '')
            ->set('form.national_id', '')
            ->set('form.nationality', '')
            ->set('form.birth_date', '')
            ->set('form.company_name', '')
            ->set('form.commercial_registration', '')
            ->set('form.contact_person_name', '')
            ->set('form.contact_person_mobile', '')
            ->set('form.notes', '')
            ->set('form.status', 'active')
            ->call('createTenant')
            ->assertHasNoErrors();

        $tenant = Tenant::where('mobile', '0577959443')->firstOrFail();

        $this->assertSame('Ahmed Tenant', $tenant->name);
        $this->assertNull($tenant->birth_date);
        $this->assertNull($tenant->email);
        $this->assertNull($tenant->national_id);
        $this->assertNull($tenant->commercial_registration);
    }

    public function test_company_tenant_ignores_individual_only_fields(): void
    {
        $this->loginAsSuperAdmin();

        Livewire::test(TenantIndex::class)
            ->set('form.type', 'company')
            ->set('form.name', 'Company Tenant')
            ->set('form.mobile', '')
            ->set('form.email', '')
            ->set('form.national_id', '1234567890')
            ->set('form.nationality', 'Saudi')
            ->set('form.birth_date', '1990-01-01')
            ->set('form.company_name', 'Company Tenant LLC')
            ->set('form.commercial_registration', '')
            ->set('form.status', 'active')
            ->call('createTenant')
            ->assertHasNoErrors();

        $tenant = Tenant::where('name', 'Company Tenant')->firstOrFail();

        $this->assertSame('company', $tenant->type);
        $this->assertNull($tenant->birth_date);
        $this->assertNull($tenant->national_id);
        $this->assertSame('Company Tenant LLC', $tenant->company_name);
        $this->assertNull($tenant->commercial_registration);
    }
}
