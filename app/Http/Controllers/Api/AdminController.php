<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function clients(Request $request)
    {
        abort_if($request->user()->role !== 'admin', 403);

        $clients = User::where('role', 'client')
            ->with('clientProfile')
            ->withCount('workers')
            ->get();

        return response()->json($clients);
    }

    public function suspendClient(Request $request, User $client)
    {
        abort_if($request->user()->role !== 'admin', 403);
        abort_if($client->role !== 'client', 422);

        $client->update(['is_suspended' => !$client->is_suspended]);

        return response()->json($client->fresh());
    }
}
