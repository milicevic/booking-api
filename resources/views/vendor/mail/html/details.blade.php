@props(['rows' => [], 'brand' => null])
<table class="details" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border-radius: {{ $brand['theme']['box'] ?? 16 }}px;">
@foreach ($rows as $label => $value)
<tr>
<td class="details-label">{{ $label }}</td>
<td class="details-value">{{ $value }}</td>
</tr>
@endforeach
</table>
