<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    /** Ažurira podešavanja klijentskog profila */
    public function updateSettings(Request $request)
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
}
