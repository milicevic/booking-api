<?php

namespace App\Services\Booking;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\Contracts\BookingCalendarInterface;
use Illuminate\Support\Carbon;

class BookingCalendar implements BookingCalendarInterface
{
    /** Podsetnik u kalendaru, minuta pre početka. */
    public const ALARM_MINUTES = 60;

    public function enabled(Booking $booking): bool
    {
        return $booking->slot->worker->client?->clientProfile->calendar_invites ?? true;
    }

    public function ics(Booking $booking): string
    {
        $cancelled = in_array($booking->status, [BookingStatus::Cancelled, BookingStatus::Rejected], true);
        [$start, $end] = $this->period($booking);
        $format = fn (Carbon $time): string => $time->copy()->utc()->format('Ymd\THis\Z');

        $event = [
            'BEGIN:VEVENT',
            'UID:'.$this->uid($booking),
            // Svaka izmena rezervacije povećava SEQUENCE, pa kalendar novu verziju prihvata kao izmenu istog događaja
            'SEQUENCE:'.$booking->updated_at->timestamp,
            'DTSTAMP:'.$format(now()),
            'DTSTART:'.$format($start),
            'DTEND:'.$format($end),
            'SUMMARY:'.$this->escape($this->title($booking)),
            'DESCRIPTION:'.$this->escape($this->description($booking)),
            'LOCATION:'.$this->escape($this->businessName($booking)),
            'URL:'.$booking->manageUrl(),
            'STATUS:'.($cancelled ? 'CANCELLED' : 'CONFIRMED'),
        ];

        if (! $cancelled) {
            $event = [...$event,
                'BEGIN:VALARM',
                'ACTION:DISPLAY',
                'DESCRIPTION:'.$this->escape($this->title($booking)),
                'TRIGGER:-PT'.self::ALARM_MINUTES.'M',
                'END:VALARM',
            ];
        }

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//'.$this->escape((string) config('app.name')).'//Booking//SR',
            'CALSCALE:GREGORIAN',
            'METHOD:'.($cancelled ? 'CANCEL' : 'PUBLISH'),
            ...$event,
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return implode("\r\n", array_map($this->fold(...), $lines))."\r\n";
    }

    public function icsUrl(Booking $booking): string
    {
        return route('bookings.calendar', ['token' => $booking->token]);
    }

    public function googleUrl(Booking $booking): string
    {
        [$start, $end] = $this->period($booking);
        $format = fn (Carbon $time): string => $time->copy()->utc()->format('Ymd\THis\Z');

        return 'https://calendar.google.com/calendar/render?'.http_build_query([
            'action' => 'TEMPLATE',
            'text' => $this->title($booking),
            'dates' => $format($start).'/'.$format($end),
            'details' => $this->description($booking),
            'location' => $this->businessName($booking),
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon} početak i kraj po lokalnom vremenu salona
     */
    private function period(Booking $booking): array
    {
        $date = $booking->slot->date->toDateString();

        return [Carbon::parse("{$date} {$booking->start_time}"), Carbon::parse("{$date} {$booking->end_time}")];
    }

    private function uid(Booking $booking): string
    {
        return "booking-{$booking->token}@".config('app.platform_host');
    }

    private function businessName(Booking $booking): string
    {
        $tenant = $booking->tenant ?? $booking->slot->worker->tenant;

        return $tenant ? ($tenant->app_name ?: $tenant->name) : (string) config('app.name');
    }

    private function title(Booking $booking): string
    {
        return $booking->service
            ? "{$booking->service->name} — {$this->businessName($booking)}"
            : $this->businessName($booking);
    }

    private function description(Booking $booking): string
    {
        return __('notifications.calendar.description', [
            'worker' => $booking->slot->worker->name,
            'url' => $booking->manageUrl(),
        ]);
    }

    /** RFC 5545: obrnuta kosa crta, tačka-zarez, zarez i novi red se pišu sa \ */
    private function escape(string $text): string
    {
        return str_replace(['\\', ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\,', '\n', '\n'], $text);
    }

    /** RFC 5545: redovi duži od 75 bajtova se lome, nastavak počinje razmakom (ne lomi UTF-8 znak). */
    private function fold(string $line): string
    {
        $parts = [];
        while (strlen($line) > 75) {
            $cut = 75;
            while ($cut > 0 && (ord($line[$cut]) & 0xC0) === 0x80) {
                $cut--;
            }
            $parts[] = substr($line, 0, $cut);
            $line = ' '.substr($line, $cut);
        }
        $parts[] = $line;

        return implode("\r\n", $parts);
    }
}
