<x-marketing-layout>
    <div class="flex flex-col gap-2">
        <div class="flex items-center gap-1 text-xs">
            <p class="tracking-wider">{{ $pagePath[0] }}</p>
            <p>></p>
            <p class="tracking-wider">{{ $pagePath[1] }}</p>
            <p>></p>
            <p class="font-bold tracking-wider text-primary">{{ $pagePath[2] }}</p>
        </div>

        <div class="mb-7 flex w-full items-center justify-between">
            <h1 class="text-4xl font-bold text-texthighlight">{{ $pageName }}</h1>
        </div>

        <form method="POST" action="{{ route('marketing.promotions.store') }}" enctype="multipart/form-data"
            class="bg-white p-6 shadow-sm">
            @include('marketing.promotions.partials.form')
        </form>
    </div>
</x-marketing-layout>
