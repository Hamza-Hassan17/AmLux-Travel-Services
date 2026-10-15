@props(['value'])
@php $withIcon = in_array($value, ['confirmed', 'paid'], true); @endphp
<span class="pill pill-{{ $value }} {{ $withIcon ? 'has-icon' : '' }}">@if ($withIcon)<x-icon name="circle-check" />@endif{{ ucfirst($value) }}</span>
