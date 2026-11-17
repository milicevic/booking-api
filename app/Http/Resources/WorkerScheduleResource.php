<?php

namespace App\Http\Resources;

use App\Models\WorkerSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WorkerSchedule
 */
class WorkerScheduleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'worker_id' => $this->worker_id,
            'weekdays' => $this->weekdays,
            'start' => substr($this->start_time, 0, 5),
            'end' => substr($this->end_time, 0, 5),
            'slot_duration' => $this->slot_duration,
            'breaks' => $this->breaks,
            'weeks_ahead' => WorkerSchedule::WEEKS_AHEAD,
            // Samo datum (Y-m-d), kao datum termina
            'generated_until' => $this->generated_until?->toDateString(),
            'updated_at' => $this->updated_at,
        ];
    }
}
