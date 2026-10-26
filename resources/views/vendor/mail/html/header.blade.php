@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ config('app.url') }}/images/logo.png" class="logo" alt="{{ config('company.name', 'ACME SHIPPING & LOGISTICS L.L.C') }}" style="max-height: 60px; width: auto;">
</a>
</td>
</tr>
