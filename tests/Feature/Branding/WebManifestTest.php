<?php

namespace Tests\Feature\Branding;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Manifest za instalaciju sajta kao aplikacije: naziv i boja firme, adrese sajta firme.
 */
class WebManifestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.platform_host' => 'slotres.click', 'app.name' => 'Termin']);
        $this->tenant->update(['subdomain' => 'salon-ana', 'app_name' => 'Frizerski salon Ana', 'primary_color' => '#be185d']);
    }

    public function test_tenant_site_gets_its_name_color_and_urls(): void
    {
        $this->getJson('/api/tenant/manifest?origin='.urlencode('https://salon-ana.slotres.click'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json')
            ->assertJsonPath('name', 'Frizerski salon Ana')
            ->assertJsonPath('short_name', 'Frizerski sa')
            ->assertJsonPath('theme_color', '#be185d')
            ->assertJsonPath('start_url', 'https://salon-ana.slotres.click/')
            ->assertJsonPath('scope', 'https://salon-ana.slotres.click/')
            ->assertJsonPath('display', 'standalone')
            ->assertJsonPath('icons.0.src', 'https://salon-ana.slotres.click/icon-192.png');
    }

    public function test_platform_host_gets_platform_manifest(): void
    {
        $this->getJson('/api/tenant/manifest?origin='.urlencode('https://slotres.click'))
            ->assertOk()
            ->assertJsonPath('name', 'Termin')
            ->assertJsonPath('theme_color', '#4f46e5');
    }

    public function test_unknown_or_invalid_origin_is_not_found(): void
    {
        $this->getJson('/api/tenant/manifest?origin='.urlencode('https://nepoznat.slotres.click'))->assertNotFound();
        $this->getJson('/api/tenant/manifest?origin=javascript:alert(1)')->assertNotFound();
        $this->getJson('/api/tenant/manifest')->assertNotFound();
    }
}
