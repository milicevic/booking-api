<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Slot\AvailableSlotsRequest;
use App\Http\Requests\Slot\BookableTimesRequest;
use App\Http\Requests\Slot\StoreSlotRequest;
use App\Http\Resources\SlotResource;
use App\Models\Slot;
use App\Services\Contracts\SlotServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        return SlotResource::collection($this->slots->availableForPublic($filters));
    }

    /** Javno — vremena u koja kupac može da zakaže kod radnika, prema trajanju izabrane usluge */
    public function times(BookableTimesRequest $request): JsonResponse
    {
        return response()->json($this->slots->bookableTimes(
            $request->integer('worker_id'),
            $request->filled('service_id') ? $request->integer('service_id') : null,
            $request->filled('date') ? $request->string('date')->toString() : null,
        ));
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        return SlotResource::collection(
            $this->slots->listFor($request->user(), $request->integer('client_id') ?: null)
        );
    }

    public function store(StoreSlotRequest $request): JsonResponse
    {
        return SlotResource::make($this->slots->create($request->slotData()))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Slot $slot): JsonResponse
    {
        Gate::authorize('delete', $slot);

        $this->slots->delete($slot);

        return response()->json(null, 204);
    }
}
