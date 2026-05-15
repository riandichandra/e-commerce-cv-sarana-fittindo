<a href="{{ $href }}">
    <button class="bg-{{ $bgColor }} text-{{ $textColor }} w-{{ $size }} py-2 px-4 flex items-center justify-center gap-2 hover:bg-{{ $bgColor }}-dark transition text-sm font-medium tracking-wider">
        @if ($icon)
            <iconify-icon icon="{{ $icon }}" class="fs-5"></iconify-icon>
        @endif
        {{ $slot }}
    </button>
</a>
