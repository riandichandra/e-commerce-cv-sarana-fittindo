<a href="{{ $href }}">
    <button
        class="bg-{{ $bgColor }} text-{{ $textColor }} w-{{ $size }} text-{{ $textSize ?? "xs" }} py-2 px-4 flex items-center justify-center gap-2 hover:bg-{{ $bgColor }}dark transition font-medium tracking-wider">
        @if ($icon)
            <iconify-icon icon="{{ $icon }}" class="fs-{{ $iconSize ?? "6" }}"></iconify-icon>
        @endif
        {{ $slot }}
    </button>
</a>
