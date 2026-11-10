@props(['brand' => null])
@php
    $brand ??= \App\Notifications\Messages\BrandedMailMessage::brand(null);
@endphp
<x-mail::layout :brand="$brand">
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="$brand['url']" :brand="$brand">
{{ $brand['name'] }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
© {{ date('Y') }} {{ $brand['name'] }}. {{ __('All rights reserved.') }}
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
