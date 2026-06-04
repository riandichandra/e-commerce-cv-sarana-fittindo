<nav x-data="{ open: false }" class="flex h-full w-full flex-col items-center justify-between border-b border-gray-100 bg-white">
    <div class="w-full">
        <div class="m-5">
            <h1 class="text-xl font-black text-primary">CV SARANA FITTINDO</h1>
            <p class="text-xs tracking-widest">GM PORTAL</p>
        </div>

        <ul class="mt-3 w-full text-sm font-medium text-gray-600">
            <a href="{{ route('gm.dashboard') }}">
                <li class="flex cursor-pointer items-center gap-3 p-3 px-5 {{ request()->routeIs('gm.dashboard') ? 'bg-primary/20 text-primary hover:bg-primary/30' : 'hover:bg-gray-200' }}">
                    <iconify-icon icon="mdi:view-dashboard" class="fs-5"></iconify-icon>
                    <p>DASBOR</p>
                </li>
            </a>
            <a href="{{ route('gm.reports.index') }}">
                <li class="flex cursor-pointer items-center gap-3 p-3 px-5 {{ request()->routeIs('gm.reports.*') ? 'bg-primary/20 text-primary hover:bg-primary/30' : 'hover:bg-gray-200' }}">
                    <iconify-icon icon="mdi:file-chart" class="fs-5"></iconify-icon>
                    <p>LAPORAN</p>
                </li>
            </a>
        </ul>
    </div>

    <div class="flex w-full flex-col gap-2 border-t border-gray-200 py-3 text-sm font-medium">
        <div class="flex w-full items-center gap-2 p-3 px-5 py-0">
            <iconify-icon icon="mdi:account-circle" class="fs-5"></iconify-icon>
            <a href="{{ route('profile.edit') }}"
                class="{{ request()->routeIs('profile.*') ? 'font-bold text-primary' : 'text-gray-600 hover:text-primary' }}">
                {{ __('PROFIL') }}
            </a>
        </div>

        <div class="flex w-full items-center gap-2 p-3 px-5 py-0">
            <iconify-icon icon="mdi:logout" class="fs-5"></iconify-icon>
            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <x-responsive-nav-link :href="route('logout')"
                    onclick="event.preventDefault(); this.closest('form').submit();">
                    {{ __('KELUAR') }}
                </x-responsive-nav-link>
            </form>
        </div>

        <div class="flex w-full items-center gap-2 p-3 px-5 py-0">
            <div class="h-8 w-8 bg-primary"></div>
            <div>
                <p class="font-bold">{{ Auth::user()->name }}</p>
                <p class="text-xs">{{ ucwords(str_replace('_', ' ', Auth::user()->getRoleNames()->first() ?? '-')) }}</p>
            </div>
        </div>
    </div>
</nav>
