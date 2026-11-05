<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AcceptInviteRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\MessageResource;
use App\Http\Resources\UserResource;
use App\Services\Contracts\AuthServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly AuthServiceInterface $auth) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $session = $this->auth->login(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
        );

        return response()->json([
            'token' => $session['token'],
            'client' => UserResource::make($session['user']),
        ]);
    }

    public function logout(Request $request): MessageResource
    {
        $this->auth->logout($request->user());

        return MessageResource::make(__('messages.logged_out'));
    }

    public function me(Request $request): UserResource
    {
        return UserResource::make($request->user()->load('clientProfile'));
    }

    public function acceptInvite(AcceptInviteRequest $request): JsonResponse
    {
        $session = $this->auth->acceptInvite(
            $request->string('invite_token')->toString(),
            $request->string('password')->toString(),
        );

        return response()->json([
            'token' => $session['token'],
            'user' => UserResource::make($session['user']),
        ]);
    }
}
