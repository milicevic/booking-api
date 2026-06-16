<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Notifications\WorkerInvite;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WorkerController extends Controller
{
    public function index(Request $request)
    {
        $clientId = $request->user()->role === 'admin' && $request->client_id
            ? $request->client_id
            : $request->user()->id;

        $workers = User::where('client_id', $clientId)
            ->where('role', 'worker')
            ->with('workerProfile')
            ->get();

        return response()->json($workers);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'phone' => 'nullable|string',
        ]);

        $inviteToken = $data['email'] ? Str::uuid()->toString() : null;

        $worker = User::create([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'role' => 'worker',
            'client_id' => $request->user()->id,
            'invite_token' => $inviteToken,
        ]);

        WorkerProfile::create([
            'user_id' => $worker->id,
            'phone' => $data['phone'] ?? null,
        ]);

        if ($inviteToken) {
            $tenant = app('current_tenant');
            $worker->notify(new WorkerInvite($tenant, $inviteToken));
        }

        return response()->json($worker->load('workerProfile'), 201);
    }

    public function show(Request $request, User $worker)
    {
        $this->authorizeOwnership($request->user(), $worker);

        return response()->json($worker->load('workerProfile'));
    }

    public function update(Request $request, User $worker)
    {
        $this->authorizeOwnership($request->user(), $worker);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'nullable|email|unique:users,email,'.$worker->id,
            'phone' => 'nullable|string',
            'can_edit_slots' => 'sometimes|boolean',
        ]);

        $worker->update(array_filter([
            'name' => $data['name'] ?? null,
            'email' => array_key_exists('email', $data) ? $data['email'] : $worker->email,
            'can_edit_slots' => $data['can_edit_slots'] ?? null,
        ], fn ($value) => $value !== null));

        if (array_key_exists('phone', $data)) {
            $worker->workerProfile()->updateOrCreate(
                ['user_id' => $worker->id],
                ['phone' => $data['phone']]
            );
        }

        return response()->json($worker->fresh()->load('workerProfile'));
    }

    public function destroy(Request $request, User $worker)
    {
        $this->authorizeOwnership($request->user(), $worker);
        $worker->delete();

        return response()->json(null, 204);
    }

    private function authorizeOwnership(User $client, User $worker): void
    {
        if ($client->role === 'admin') {
            return;
        }

        abort_if($worker->client_id !== $client->id, 403);
    }
}
