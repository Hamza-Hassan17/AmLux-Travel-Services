@props(['value' => null])
@if ($value)
    <span class="pay-{{ $value }}">{{ ucfirst($value) }}</span>
@else
    <span class="muted">—</span>
@endif
