<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Worker\StoreWorkerRequest;
use App\Http\Requests\Worker\UpdateWorkerRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Contracts\WorkerServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class WorkerController extends Controller
{
    public function __construct(private readonly WorkerServiceInterface $workers) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return UserResource::collection($this->workers->listFor($request->user(), $request->integer('client_id') ?: null));
    }

    public function store(StoreWorkerRequest $request): JsonResponse
    {
        /** @var array{name: string, email?: ?string, phone?: ?string} $data */
        $data = $request->validated();

        return UserResource::make($this->workers->create($request->user(), $data))
            ->response()
            ->setStatusCode(201);
    }

    public function show(User $worker): UserResource
    {
        Gate::authorize('view', $worker);

        return UserResource::make($worker->load('workerProfile'));
    }

    public function update(UpdateWorkerRequest $request, User $worker): UserResource
    {
        /** @var array{name?: string, email?: ?string, phone?: ?string, can_edit_slots?: bool} $data */
        $data = $request->validated();

        return UserResource::make($this->workers->update($worker, $data));
    }

    public function destroy(User $worker): JsonResponse
    {
        Gate::authorize('delete', $worker);

        $this->workers->delete($worker);

        return response()->json(null, 204);
    }
}
