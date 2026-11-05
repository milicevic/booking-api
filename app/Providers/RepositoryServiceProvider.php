<?php

namespace App\Providers;

use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\ClientRepositoryInterface;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Contracts\ServiceRepositoryInterface;
use App\Repositories\Contracts\SlotRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Repositories\Contracts\UserLookupRepositoryInterface;
use App\Repositories\Contracts\WorkerRepositoryInterface;
use App\Repositories\Eloquent\EloquentBookingRepository;
use App\Repositories\Eloquent\EloquentClientRepository;
use App\Repositories\Eloquent\EloquentNotificationRepository;
use App\Repositories\Eloquent\EloquentServiceRepository;
use App\Repositories\Eloquent\EloquentSlotRepository;
use App\Repositories\Eloquent\EloquentTenantRepository;
use App\Repositories\Eloquent\EloquentUserLookupRepository;
use App\Repositories\Eloquent\EloquentWorkerRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Vezuje ugovore repozitorijuma za Eloquent implementacije.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $singletons = [
        BookingRepositoryInterface::class => EloquentBookingRepository::class,
        ClientRepositoryInterface::class => EloquentClientRepository::class,
        NotificationRepositoryInterface::class => EloquentNotificationRepository::class,
        ServiceRepositoryInterface::class => EloquentServiceRepository::class,
        SlotRepositoryInterface::class => EloquentSlotRepository::class,
        TenantRepositoryInterface::class => EloquentTenantRepository::class,
        UserLookupRepositoryInterface::class => EloquentUserLookupRepository::class,
        WorkerRepositoryInterface::class => EloquentWorkerRepository::class,
    ];
}
