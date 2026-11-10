@props([
    'url',
    'color' => 'primary',
    'align' => 'center',
    'brand' => null,
])
@php
    // Primarno dugme dobija boju firme (kao --primary u aplikaciji), zaobljenje prati temu
    $radius = $brand['theme']['button'] ?? 10;
    $style = "border-radius: {$radius}px;";
    if ($color === 'primary' && $brand) {
        $style .= " background-color: {$brand['color']}; border-color: {$brand['color']}; color: {$brand['foreground']};";
    }
@endphp
<table class="action" align="{{ $align }}" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="{{ $align }}">
<table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="{{ $align }}">
<table border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td>
<a href="{{ $url }}" class="button button-{{ $color }}" target="_blank" rel="noopener" style="{{ $style }}">{!! $slot !!}</a>
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
</table>
