<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Service\StoreServiceRequest;
use App\Http\Requests\Service\UpdateServiceRequest;
use App\Http\Resources\MessageResource;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Models\User;
use App\Services\Contracts\ServiceCatalogServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ServiceController extends Controller
{
    public function __construct(private readonly ServiceCatalogServiceInterface $catalog) {}

    /** Usluge firme; opciono ?worker_id=X za usluge koje radi taj radnik. */
    public function index(Request $request): AnonymousResourceCollection
    {
        return ServiceResource::collection($this->catalog->list($request->integer('worker_id') ?: null));
    }

    public function store(StoreServiceRequest $request): JsonResponse
    {
        return ServiceResource::make($this->catalog->create($request->validated()))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateServiceRequest $request, Service $service): ServiceResource
    {
        return ServiceResource::make($this->catalog->update($service, $request->validated()));
    }

    public function destroy(Service $service): JsonResponse
    {
        Gate::authorize('delete', $service);

        $this->catalog->delete($service);

        return response()->json(null, 204);
    }

    public function assignWorker(Service $service, User $worker): MessageResource
    {
        Gate::authorize('manageWorker', [$service, $worker]);

        $this->catalog->assignWorker($service, $worker);

        return MessageResource::make(__('messages.service_assigned'));
    }

    public function unassignWorker(Service $service, User $worker): JsonResponse
    {
        Gate::authorize('manageWorker', [$service, $worker]);

        $this->catalog->unassignWorker($service, $worker);

        return response()->json(null, 204);
    }
}
