<?php

namespace Tests\Feature\Architecture;

use App\Providers\DomainServiceProvider;
use App\Providers\RepositoryServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Svaki ugovor (interfejs) mora biti vezan za implementaciju koja ga ispunjava.
 */
class ContainerBindingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_repository_contract_resolves_to_its_implementation(): void
    {
        $this->assertBindingsResolve((new RepositoryServiceProvider($this->app))->singletons);
    }

    public function test_every_service_contract_resolves_to_its_implementation(): void
    {
        $this->assertBindingsResolve((new DomainServiceProvider($this->app))->singletons);
    }

    public function test_every_contract_in_contracts_directories_is_bound(): void
    {
        $bound = [
            ...array_keys((new RepositoryServiceProvider($this->app))->singletons),
            ...array_keys((new DomainServiceProvider($this->app))->singletons),
        ];

        foreach (['Repositories', 'Services'] as $layer) {
            foreach (glob(app_path("{$layer}/Contracts/*.php")) ?: [] as $file) {
                $contract = "App\\{$layer}\\Contracts\\".basename($file, '.php');
                $this->assertContains($contract, $bound, "{$contract} is not bound in the container.");
            }
        }
    }

    /**
     * @param  array<class-string, class-string>  $bindings
     */
    private function assertBindingsResolve(array $bindings): void
    {
        $this->assertNotEmpty($bindings);

        foreach ($bindings as $contract => $implementation) {
            $this->assertTrue(interface_exists($contract), "{$contract} must be an interface.");
            $this->assertInstanceOf($implementation, app($contract));
            $this->assertInstanceOf($contract, app($contract));
        }
    }
}
