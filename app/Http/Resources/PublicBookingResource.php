<?php

namespace App\Http\Resources;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\Contracts\BookingCalendarInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Rezervacija kako je vidi kupac (po tokenu): radnik bez emaila, telefona i podataka naloga.
 *
 * @mixin Booking
 */
class PublicBookingResource extends JsonResource
{
    /**
     * @return array{google_url: string, ics_url: string}|null
     */
    private function calendarLinks(): ?array
    {
        $calendar = app(BookingCalendarInterface::class);

        if (! in_array($this->status, BookingStatus::active(), true) || ! $calendar->enabled($this->resource)) {
            return null;
        }

        return ['google_url' => $calendar->googleUrl($this->resource), 'ics_url' => $calendar->icsUrl($this->resource)];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slot_id' => $this->slot_id,
            'service_id' => $this->service_id,
            'token' => $this->token,
            'status' => $this->status,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'customer_name' => $this->customer_name,
            'customer_email' => $this->customer_email,
            'customer_phone' => $this->customer_phone,
            'note' => $this->note,
            'created_at' => $this->created_at,
            // Da li kupac sada sme sam da pomeri rezervaciju, i do koliko sati pre početka
            'can_reschedule' => $this->whenLoaded('slot', fn () => $this->canBeRescheduledByCustomer()),
            'reschedule_cutoff_hours' => $this->whenLoaded('slot', fn () => $this->rescheduleCutoffHours()),
            // Dodavanje u kalendar: Google link (Android, web) i .ics (iPhone); null ako ga je vlasnik isključio
            'calendar' => $this->whenLoaded('slot', fn () => $this->calendarLinks()),
            'slot' => $this->whenLoaded('slot', fn () => PublicSlotResource::make($this->slot)),
            'service' => $this->whenLoaded('service', fn () => ServiceResource::make($this->service)),
        ];
    }
}
