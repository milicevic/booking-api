<?php

namespace Tests\Feature\Http;

use App\Http\Middleware\HandleTenantCors;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class TenantCorsTest extends TestCase
{
    use RefreshDatabase;

    private function allowedOriginFor(string $origin): ?string
    {
        $request = Request::create('/api/tenant/config', 'GET', server: ['HTTP_ORIGIN' => $origin]);

        /** @var Response $response */
        $response = $this->app->make(HandleTenantCors::class)->handle($request, fn () => response('ok'));

        return $response->headers->get('Access-Control-Allow-Origin');
    }

    public function test_localhost_origins_are_allowed_in_local_development(): void
    {
        $this->app['env'] = 'local';

        $this->assertSame('http://salon-a.localhost:5173', $this->allowedOriginFor('http://salon-a.localhost:5173'));
    }

    public function test_localhost_origins_are_rejected_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->assertNull($this->allowedOriginFor('http://salon-a.localhost:5173'));
        $this->assertNull($this->allowedOriginFor('http://localhost:5173'));
    }

    public function test_tenant_site_is_allowed_in_production(): void
    {
        $this->app['env'] = 'production';
        config(['app.platform_host' => 'slotres.click']);
        $this->tenant->update(['subdomain' => 'salon-a']);

        $this->assertSame('https://salon-a.slotres.click', $this->allowedOriginFor('https://salon-a.slotres.click'));
    }

    public function test_arbitrary_vercel_apps_are_not_allowed(): void
    {
        $this->withHeader('Origin', 'https://bilo-ko.vercel.app')
            ->getJson('/api/tenant/config')
            ->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    public function test_explicitly_configured_frontend_origin_is_allowed(): void
    {
        $this->app['env'] = 'production';
        config(['app.cors_allowed_origins' => 'https://slotres.vercel.app']);

        $this->assertSame('https://slotres.vercel.app', $this->allowedOriginFor('https://slotres.vercel.app'));
        $this->assertNull($this->allowedOriginFor('https://bilo-ko.vercel.app'));
    }
}
