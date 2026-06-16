<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Http\Request;

class SlotController extends Controller
{
    // Javna ruta — korisnik vidi slobodne slotove
    public function available(Request $request)
    {
        $request->validate([
            'date' => 'sometimes|date',
            'worker_id' => 'sometimes|exists:users,id',
            'service_id' => 'sometimes|exists:services,id',
        ]);

        $slots = Slot::with('worker')
            ->where('is_available', true)
            ->when($request->date, fn ($q) => $q->whereDate('date', $request->date))
            ->when($request->worker_id, fn ($q) => $q->where('worker_id', $request->worker_id))
            ->when($request->service_id, fn ($q) => $q->whereHas(
                'worker.services',
                fn ($s) => $s->where('services.id', $request->service_id)->where('services.is_active', true)
            ))
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return response()->json($slots);
    }

    // Admin/Worker rute
    public function index(Request $request)
    {
        if ($request->user()->role === 'admin' && $request->client_id) {
            $workerIds = User::where('client_id', $request->client_id)->where('role', 'worker')->pluck('id');

            $slots = Slot::with('worker', 'booking')
                ->whereIn('worker_id', $workerIds)
                ->orderBy('date')
                ->orderBy('start_time')
                ->get();

            return response()->json($slots);
        }

        if ($request->user()->role === 'worker') {
            $slots = Slot::with('worker', 'booking')
                ->where('worker_id', $request->user()->id)
                ->orderBy('date')
                ->orderBy('start_time')
                ->get();

            return response()->json($slots);
        }

        $workerIds = $request->user()->workers()->pluck('id');

        $slots = Slot::with('worker', 'booking')
            ->whereIn('worker_id', $workerIds)
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return response()->json($slots);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'worker_id' => 'required|exists:users,id',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        $worker = User::findOrFail($data['worker_id']);
        if ($request->user()->role !== 'admin') {
            abort_if($worker->client_id !== $request->user()->id, 403);
        }

        $slot = Slot::create($data);

        return response()->json($slot, 201);
    }

    public function destroy(Request $request, Slot $slot)
    {
        abort_if($slot->worker->client_id !== $request->user()->id, 403);
        $slot->delete();

        return response()->json(null, 204);
    }
}
