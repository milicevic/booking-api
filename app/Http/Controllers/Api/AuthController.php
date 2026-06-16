<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::withoutGlobalScopes()->where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => __('messages.wrong_credentials')], 401);
        }

        if ($user->is_suspended) {
            return response()->json(['message' => __('messages.account_suspended')], 403);
        }

        $isPlatformUser = in_array($user->role, ['superadmin', 'admin']);
        $currentTenant = app()->bound('current_tenant') ? app('current_tenant') : null;

        if (! $isPlatformUser && $currentTenant && $user->tenant_id !== $currentTenant->id) {
            return response()->json(['message' => __('messages.wrong_credentials')], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'client' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => __('messages.logged_out')]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load('clientProfile'));
    }

    public function acceptInvite(Request $request): JsonResponse
    {
        $data = $request->validate([
            'invite_token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        /** @var User|null $worker */
        $worker = User::withoutGlobalScopes()
            ->where('invite_token', $data['invite_token'])
            ->where('role', 'worker')
            ->first();

        abort_if(! $worker, 404, __('messages.invalid_invite_token'));

        $worker->update([
            'password' => $data['password'],
            'invite_token' => null,
        ]);

        $token = $worker->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $worker->fresh(),
        ]);
    }
}
