@props(['background' => '#f3f4f6', 'color' => '#111827'])
<table class="badge-wrapper" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="badge" style="background-color: {{ $background }}; color: {{ $color }};">{{ trim($slot) }}</td>
</tr>
</table>
