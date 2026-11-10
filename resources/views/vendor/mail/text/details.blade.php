@props(['rows' => [], 'brand' => null])
@foreach ($rows as $label => $value)
{{ $label }}: {{ $value }}
@endforeach
