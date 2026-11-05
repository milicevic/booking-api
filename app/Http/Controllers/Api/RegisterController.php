<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Register\RegisterRequest;
use App\Http\Requests\Register\SubdomainPreviewRequest;
use App\Http\Resources\TenantResource;
use App\Http\Resources\UserResource;
use App\Services\Contracts\SubdomainGeneratorInterface;
use App\Services\Contracts\TenantRegistrationServiceInterface;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    public function store(RegisterRequest $request, TenantRegistrationServiceInterface $registration): JsonResponse
    {
        /** @var array{name: string, email: string, password: string, app_name: string, subdomain?: string|null} $data */
        $data = $request->validated();
        [$tenant, $user] = $registration->register($data);

        return response()->json([
            'tenant' => TenantResource::make($tenant),
            'user' => UserResource::make($user),
        ], 201);
    }

    /** Adresa (poddomen) koju će firma dobiti, dok korisnik kuca naziv na registraciji. */
    public function subdomainPreview(SubdomainPreviewRequest $request, SubdomainGeneratorInterface $subdomains): JsonResponse
    {
        return response()->json([
            'subdomain' => $subdomains->uniqueFor($request->string('app_name')->toString()),
        ]);
    }
}
