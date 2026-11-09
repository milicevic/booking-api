@php
    // Mejlovi bez brenda (npr. sistemski) dobijaju podrazumevani izgled platforme
    $brand ??= \App\Notifications\Messages\BrandedMailMessage::brand(null);
@endphp
<x-mail::message :brand="$brand">
{{-- Status --}}
@isset($status)
<x-mail::badge :background="$status['background']" :color="$status['color']">{{ $status['label'] }}</x-mail::badge>
@endisset

{{-- Greeting --}}
@if (! empty($greeting))
# {{ $greeting }}
@else
@if ($level === 'error')
# @lang('Whoops!')
@else
# @lang('Hello!')
@endif
@endif

{{-- Intro Lines --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- Booking details --}}
@if (! empty($details))
<x-mail::details :rows="$details" :brand="$brand" />
@endif

{{-- Action Button --}}
@isset($actionText)
<x-mail::button :url="$actionUrl" :brand="$brand">
{{ $actionText }}
</x-mail::button>
@endisset

@isset($secondaryAction)
<x-mail::button :url="$secondaryAction['url']" color="destructive" :brand="$brand">
{{ $secondaryAction['text'] }}
</x-mail::button>
@endisset

{{-- Outro Lines --}}
@foreach ($outroLines as $line)
{{ $line }}

@endforeach

{{-- Salutation --}}
@if (! empty($salutation))
{{ $salutation }}
@else
@lang('Regards,')<br>
{{ $brand['name'] }}
@endif

{{-- Subcopy --}}
@isset($actionText)
<x-slot:subcopy>
@lang(
    "If you're having trouble clicking the \":actionText\" button, copy and paste the URL below\n".
    'into your web browser:',
    [
        'actionText' => $actionText,
    ]
) <span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
@endisset
</x-mail::message>
