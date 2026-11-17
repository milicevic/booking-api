@props(['url', 'brand' => null])
@php
    // Bez loga: kvadrat u boji firme sa početnim slovom, kao zaglavlje stranice za zakazivanje
    $name = trim($slot);
    $initial = mb_strtoupper(mb_substr($name, 0, 1));
@endphp
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<table cellpadding="0" cellspacing="0" role="presentation" align="center">
<tr>
@if (! empty($brand['logo']))
<td style="padding-right: 12px; vertical-align: middle;"><img src="{{ $brand['logo'] }}" class="logo" alt="" width="40" height="40" style="height: 40px; width: 40px; border-radius: 10px; object-fit: contain;"></td>
@elseif ($brand)
<td style="padding-right: 12px; vertical-align: middle;"><div class="brand-mark" style="background-color: {{ $brand['color'] }}; color: {{ $brand['foreground'] }};">{{ $initial }}</div></td>
@endif
<td class="brand-name" style="vertical-align: middle;">{{ $name }}</td>
</tr>
</table>
</a>
</td>
</tr>
