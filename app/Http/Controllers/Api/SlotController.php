<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Slot\AvailableSlotsRequest;
use App\Http\Requests\Slot\BookableTimesRequest;
use App\Http\Requests\Slot\BulkStoreSlotsRequest;
use App\Http\Requests\Slot\ListSlotsRequest;
use App\Http\Requests\Slot\StoreSlotRequest;
use App\Http\Requests\Slot\UpdateSlotRequest;
use App\Http\Resources\PublicSlotResource;
use App\Http\Resources\SlotResource;
use App\Models\Slot;
use App\Services\Contracts\SlotServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class SlotController extends Controller
{
    public function __construct(private readonly SlotServiceInterface $slots) {}

    /** Javno — slobodni termini */
    public function available(AvailableSlotsRequest $request): AnonymousResourceCollection
    {
        /** @var array{date?: ?string, worker_id?: int|string|null, service_id?: int|string|null} $filters */
        $filters = $request->validated();

        return PublicSlotResource::collection($this->slots->availableForPublic($filters));
    }

    /** Javno — vremena za izabranu uslugu, kod jednog radnika ili kod svih koji je rade (vidi docs/booking-flow.md) */
    public function times(BookableTimesRequest $request): JsonResponse
    {
        return response()->json($this->slots->bookableTimes(
            $request->filled('worker_id') ? $request->integer('worker_id') : null,
            $request->integer('service_id'),
            $request->filled('date') ? $request->string('date')->toString() : null,
        ));
    }

    /** Termini koje korisnik sme da vidi; filteri from, to, worker_id i opciono per_page */
    public function index(ListSlotsRequest $request): AnonymousResourceCollection
    {
        return SlotResource::collection(
            $this->slots->listFor($request->user(), $request->integer('client_id') ?: null, $request->filters(), $request->perPage())
        );
    }

    public function store(StoreSlotRequest $request): JsonResponse
    {
        return SlotResource::make($this->slots->create($request->slotData()))
            ->response()
            ->setStatusCode(201);
    }

    /** Grupno pravljenje termina (onboarding, generator); odgovor: koliko je napravljeno, a koliko preskočeno */
    public function bulkStore(BulkStoreSlotsRequest $request): JsonResponse
    {
        return response()->json($this->slots->createMany($request->bulkData()), 201);
    }

    /** Blokiranje / odblokiranje termina */
    public function update(UpdateSlotRequest $request, Slot $slot): SlotResource
    {
        Gate::authorize('update', $slot);

        return SlotResource::make($this->slots->setBlocked($slot, $request->boolean('is_blocked')));
    }

    public function destroy(Slot $slot): JsonResponse
    {
        Gate::authorize('delete', $slot);

        $this->slots->delete($slot);

        return response()->json(null, 204);
    }
}
