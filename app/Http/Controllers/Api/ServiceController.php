<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * List all services for the tenant.
     * Optional ?worker_id=X to filter services assigned to that worker.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Service::query();

        if ($request->worker_id) {
            $query->whereHas('workers', fn ($q) => $q->where('users.id', $request->worker_id));
        }

        return response()->json($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        abort_if(! in_array($request->user()->role, ['client', 'admin']), 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'duration_minutes' => 'required|integer|min:1',
            'price' => 'nullable|numeric|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $service = Service::create($data);

        return response()->json($service, 201);
    }

    public function update(Request $request, Service $service): JsonResponse
    {
        abort_if(! in_array($request->user()->role, ['client', 'admin']), 403);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'duration_minutes' => 'sometimes|integer|min:1',
            'price' => 'nullable|numeric|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $service->update($data);

        return response()->json($service->fresh());
    }

    public function destroy(Request $request, Service $service): JsonResponse
    {
        abort_if(! in_array($request->user()->role, ['client', 'admin']), 403);

        $service->delete();

        return response()->json(null, 204);
    }

    /**
     * Assign a service to a worker (client/admin only).
     */
    public function assignWorker(Request $request, Service $service, User $worker): JsonResponse
    {
        abort_if(! in_array($request->user()->role, ['client', 'admin']), 403);
        $this->authorizeWorkerAccess($request->user(), $worker);

        $service->workers()->syncWithoutDetaching([$worker->id]);

        return response()->json(['message' => __('messages.service_assigned')]);
    }

    /**
     * Remove a service from a worker (client/admin only).
     */
    public function unassignWorker(Request $request, Service $service, User $worker): JsonResponse
    {
        abort_if(! in_array($request->user()->role, ['client', 'admin']), 403);
        $this->authorizeWorkerAccess($request->user(), $worker);

        $service->workers()->detach($worker->id);

        return response()->json(null, 204);
    }

    /**
     * Ensure the authenticated client owns the worker.
     * Admin can access any worker within the tenant.
     */
    private function authorizeWorkerAccess(User $user, User $worker): void
    {
        abort_if($worker->role !== 'worker', 422);

        if ($user->role === 'admin') {
            return;
        }

        abort_if($worker->client_id !== $user->id, 403);
    }
}
