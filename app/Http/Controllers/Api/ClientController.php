<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    /** Ažurira podešavanja klijentskog profila */
    public function updateSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'auto_confirm_bookings' => 'sometimes|boolean',
        ]);

        $request->user()->clientProfile()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $data
        );

        return response()->json($request->user()->fresh()->load('clientProfile'));
    }

    /** Klijent šalje zahtev za deploy (subdomena ili custom domena) */
    public function deployRequest(Request $request): JsonResponse
    {
        abort_if($request->user()->role !== 'client', 403);

        $data = $request->validate([
            'subdomain' => ['nullable', 'string', 'alpha_dash', 'max:63'],
            'custom_domain' => ['nullable', 'string', 'max:253'],
        ]);

        $tenant = app('current_tenant');

        $updates = ['deploy_status' => 'pending_deploy'];

        if (! empty($data['subdomain'])) {
            $updates['subdomain'] = $data['subdomain'];
        }

        if (! empty($data['custom_domain'])) {
            $updates['custom_domain'] = $data['custom_domain'];
        }

        $tenant->update($updates);

        return response()->json($tenant->fresh());
    }
}
