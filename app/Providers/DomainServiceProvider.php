<?php

namespace App\Providers;

use App\Services\Auth\AuthService;
use App\Services\Auth\EmailVerificationService;
use App\Services\Auth\GoogleAuthService;
use App\Services\Auth\SanctumTokenIssuer;
use App\Services\Booking\BookingNotifier;
use App\Services\Booking\BookingService;
use App\Services\Catalog\ServiceCatalogService;
use App\Services\Client\ClientService;
use App\Services\Contracts\AuthServiceInterface;
use App\Services\Contracts\BookingNotifierInterface;
use App\Services\Contracts\BookingServiceInterface;
use App\Services\Contracts\ClientServiceInterface;
use App\Services\Contracts\CurrentTenantInterface;
use App\Services\Contracts\EmailVerificationServiceInterface;
use App\Services\Contracts\GoogleAuthServiceInterface;
use App\Services\Contracts\LogoServiceInterface;
use App\Services\Contracts\PushSubscriptionServiceInterface;
use App\Services\Contracts\ServiceCatalogServiceInterface;
use App\Services\Contracts\SlotAvailabilityServiceInterface;
use App\Services\Contracts\SlotServiceInterface;
use App\Services\Contracts\SubdomainGeneratorInterface;
use App\Services\Contracts\TenantRegistrationServiceInterface;
use App\Services\Contracts\TenantServiceInterface;
use App\Services\Contracts\TokenIssuerInterface;
use App\Services\Contracts\UserNotificationServiceInterface;
use App\Services\Contracts\VisibleWorkersResolverInterface;
use App\Services\Contracts\WorkerServiceInterface;
use App\Services\Notification\UserNotificationService;
use App\Services\Push\PushSubscriptionService;
use App\Services\Slot\SlotAvailabilityService;
use App\Services\Slot\SlotService;
use App\Services\Tenancy\CurrentTenant;
use App\Services\Tenant\LogoService;
use App\Services\Tenant\SubdomainGenerator;
use App\Services\Tenant\TenantRegistrationService;
use App\Services\Tenant\TenantService;
use App\Services\Worker\VisibleWorkersResolver;
use App\Services\Worker\WorkerService;
use Illuminate\Support\ServiceProvider;

/**
 * Vezuje ugovore servisa za implementacije. Servisi nemaju stanje, pa su singletoni;
 * CurrentTenant stanje drži u containeru, pa se reset containera između testova poštuje.
 */
class DomainServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $singletons = [
        AuthServiceInterface::class => AuthService::class,
        BookingNotifierInterface::class => BookingNotifier::class,
        BookingServiceInterface::class => BookingService::class,
        ClientServiceInterface::class => ClientService::class,
        CurrentTenantInterface::class => CurrentTenant::class,
        EmailVerificationServiceInterface::class => EmailVerificationService::class,
        GoogleAuthServiceInterface::class => GoogleAuthService::class,
        LogoServiceInterface::class => LogoService::class,
        PushSubscriptionServiceInterface::class => PushSubscriptionService::class,
        ServiceCatalogServiceInterface::class => ServiceCatalogService::class,
        SlotAvailabilityServiceInterface::class => SlotAvailabilityService::class,
        SlotServiceInterface::class => SlotService::class,
        SubdomainGeneratorInterface::class => SubdomainGenerator::class,
        TenantRegistrationServiceInterface::class => TenantRegistrationService::class,
        TenantServiceInterface::class => TenantService::class,
        TokenIssuerInterface::class => SanctumTokenIssuer::class,
        UserNotificationServiceInterface::class => UserNotificationService::class,
        VisibleWorkersResolverInterface::class => VisibleWorkersResolver::class,
        WorkerServiceInterface::class => WorkerService::class,
    ];
}
