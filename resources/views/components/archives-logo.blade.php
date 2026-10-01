@props([
    'class' => 'h-8 w-8 object-contain',
    'alt' => 'ARCHIVES',
])

<img src="{{ asset('storage/logo/archives_logo.png') }}" alt="{{ $alt }}" {{ $attributes->merge(['class' => $class]) }}>