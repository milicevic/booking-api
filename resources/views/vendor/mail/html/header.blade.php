@props(['url', 'brand' => null])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<table cellpadding="0" cellspacing="0" role="presentation" align="center">
<tr>
@if (! empty($brand['logo']))
<td style="padding-right: 10px; vertical-align: middle;"><img src="{{ $brand['logo'] }}" class="logo" alt="" width="36" height="36" style="height: 36px; width: 36px; border-radius: 8px; object-fit: contain;"></td>
@endif
<td class="brand-name" style="vertical-align: middle;">{{ trim($slot) }}</td>
</tr>
</table>
</a>
</td>
</tr>
