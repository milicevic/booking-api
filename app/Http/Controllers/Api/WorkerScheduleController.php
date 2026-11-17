<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Worker\SaveWorkerScheduleRequest;
use App\Http\Resources\WorkerScheduleResource;
use App\Models\Slot;
use App\Models\User;
use App\Services\Contracts\WorkerScheduleServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Stalni raspored radnika. Uređuje ga ko sme da pravi termine tom radniku (SlotPolicy::create):
 * vlasnik firme, a radnik samo svoj i uz can_edit_slots.
 */
class WorkerScheduleController extends Controller
{
    public function __construct(private readonly WorkerScheduleServiceInterface $schedules) {}

    /** Raspored radnika ili null ako ga nema */
    public function show(User $worker): JsonResponse
    {
        Gate::authorize('create', [Slot::class, $worker]);

        $schedule = $this->schedules->find($worker);

        return response()->json($schedule ? WorkerScheduleResource::make($schedule) : null);
    }

    /** Čuva raspored i odmah pravi termine za narednih WEEKS_AHEAD nedelja */
    public function update(SaveWorkerScheduleRequest $request, User $worker): JsonResponse
    {
        Gate::authorize('create', [Slot::class, $worker]);

        $result = $this->schedules->save($worker, $request->scheduleData());

        // Resursi se ne omotavaju (withoutWrapping), pa additional() ne bi stigao do odgovora
        return response()->json([
            ...WorkerScheduleResource::make($result['schedule'])->resolve($request),
            'generated' => [
                'created' => $result['created'],
                'skipped' => $result['skipped'],
                'removed' => $result['removed'],
            ],
        ]);
    }

    /** Raspored prestaje da pravi termine; postojeći termini ostaju */
    public function destroy(User $worker): JsonResponse
    {
        Gate::authorize('create', [Slot::class, $worker]);

        $this->schedules->delete($worker);

        return response()->json(null, 204);
    }
}
