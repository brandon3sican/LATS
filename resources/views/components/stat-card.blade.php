@props([
    'title',
    'value' => 0,
    'variant' => 'primary',
    'link' => null,
    'linkText' => null,
])

@php
    $formatted = number_format((int) $value);
@endphp

<div class="card text-bg-{{ $variant }} h-100" role="group" aria-label="{{ $title }}: {{ $formatted }}">
    <div class="card-body d-flex flex-column">
        <h5 class="card-title">{{ $title }}</h5>
        <p class="display-4 fw-bold mb-2">{{ $formatted }}</p>

        @if ($link && $linkText)
            <a href="{{ $link }}" class="btn btn-light btn-sm text-{{ $variant }} mt-auto align-self-start">
                {{ $linkText }}
            </a>
        @endif
    </div>
</div>
