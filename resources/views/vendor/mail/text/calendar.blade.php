@props(['calendar', 'brand' => null])
@if ($calendar['cancelled'])
{{ __('notifications.calendar.removed') }}
@else
{{ __('notifications.calendar.title', ['minutes' => \App\Services\Booking\BookingCalendar::ALARM_MINUTES]) }}
{{ __('notifications.calendar.google') }}: {{ $calendar['google'] }}
{{ __('notifications.calendar.apple') }}: {{ $calendar['ics'] }}
@endif
