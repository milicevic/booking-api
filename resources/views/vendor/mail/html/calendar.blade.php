@props(['calendar', 'brand' => null])
@if ($calendar['cancelled'])
<p class="calendar-note">{{ __('notifications.calendar.removed') }}</p>
@else
<table class="calendar" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="calendar-title">{{ __('notifications.calendar.title', ['minutes' => \App\Services\Booking\BookingCalendar::ALARM_MINUTES]) }}</td>
</tr>
<tr>
<td class="calendar-links">
<a href="{{ $calendar['google'] }}" class="calendar-link" style="border-radius: {{ $brand['theme']['button'] ?? 10 }}px;">{{ __('notifications.calendar.google') }}</a>
<a href="{{ $calendar['ics'] }}" class="calendar-link" style="border-radius: {{ $brand['theme']['button'] ?? 10 }}px;">{{ __('notifications.calendar.apple') }}</a>
</td>
</tr>
</table>
@endif
