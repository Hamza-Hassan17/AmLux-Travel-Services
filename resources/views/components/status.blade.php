@props(['value'])
<span class="pill pill-{{ $value }} {{ $value === 'confirmed' ? 'has-icon' : '' }}">@if ($value === 'confirmed')<x-icon name="circle-check" />@endif{{ ucfirst($value) }}</span>
