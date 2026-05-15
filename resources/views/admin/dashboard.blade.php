<x-admin-layout>
    <div class="flex flex-col gap-2">
        <div class="text-sm flex items-center gap-1">
            <p class="tracking-wider">{{ $pagePath[0] }}</p>
            <p>></p>
            <p class="font-bold text-primary tracking-wider">{{ $pagePath[1] }}</p>
        </div>
        <h1 class="text-3xl font-bold text-texthighlight">{{ $pageName }}</h1>
    </div>
</x-admin-layout>
