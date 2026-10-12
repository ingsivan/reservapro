@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<span style="display: inline-block; width: 32px; height: 32px; line-height: 32px; border-radius: 8px; background-color: #0d9488; color: #ffffff; font-size: 18px; text-align: center; vertical-align: middle;">&#10003;</span>
<span style="vertical-align: middle; margin-left: 6px;">{!! $slot !!}</span>
</a>
</td>
</tr>
