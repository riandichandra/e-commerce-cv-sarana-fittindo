<nav x-data="{ open: false }"
    class="border-b border-gray-100 flex flex-col items-center justify-between h-full w-full">
    <div class="w-full">
        <div class="m-5">
            <h1 class="text-xl font-black text-primary">CV SARANA FITTINDO</h1>
            <p class="text-xs tracking-widest">MANAGEMENT PORTAL</p>
        </div>
        <ul class="w-full mt-3 text-gray-600 text-sm font-medium">
            <a href="{{ route('admin.dashboard') }}">
                <li class="p-3 px-5 flex items-center gap-3 cursor-pointer {{ request()->routeIs('admin.dashboard') ? 'bg-primary/20 text-primary hover:bg-primary/30' : 'hover:bg-gray-200' }} {{ request()->routeIs('admin.dashboard') ? 'bg-primary/20 text-primary hover:bg-primary/30' : 'hover:bg-gray-200' }}">
                    <iconify-icon icon="mdi:home" class="nav-small-cap-icon fs-5"></iconify-icon>
                    <p>DASHBOARD</p>
                </li>
            </a>
            <a href="{{ route('admin.products.index') }}">
                <li class="p-3 px-5 flex items-center gap-3 hover:bg-gray-200 cursor-pointer {{ request()->routeIs('admin.products.*') ? 'bg-primary/20 text-primary hover:bg-primary/30' : 'hover:bg-gray-200' }}">
                    <iconify-icon icon="mdi:box-variant" class="nav-small-cap-icon fs-5"></iconify-icon>
                    <p>PRODUCTS</p>
                </li>
            </a>
            <li class="p-3 px-5 flex items-center gap-3 hover:bg-gray-200 cursor-pointer {{ request()->routeIs('admin.orders.index') ? 'bg-primary/20 text-primary hover:bg-primary/30' : 'hover:bg-gray-200' }}">
                <iconify-icon icon="mdi:shopping" class="nav-small-cap-icon fs-5"></iconify-icon>
                <p>ORDERS</p>
            </li>
            <li class="p-3 px-5 flex items-center gap-3 hover:bg-gray-200 cursor-pointer {{ request()->routeIs('admin.payments.index') ? 'bg-primary/20 text-primary hover:bg-primary/30' : 'hover:bg-gray-200' }}">
                <iconify-icon icon="mdi:money" class="nav-small-cap-icon fs-5"></iconify-icon>
                <p>PAYMENTS</p>
            </li>
            <li class="p-3 px-5 flex items-center gap-3 hover:bg-gray-200 cursor-pointer {{ request()->routeIs('admin.promotions.index') ? 'bg-primary/20 text-primary hover:bg-primary/30' : 'hover:bg-gray-200' }}">
                <iconify-icon icon="mdi:loudspeaker" class="nav-small-cap-icon fs-5"></iconify-icon>
                <p>PROMOTIONS</p>
            </li>
            <li class="p-3 px-5 flex items-center gap-3 hover:bg-gray-200 cursor-pointer {{ request()->routeIs('admin.reports.index') ? 'bg-primary/20 text-primary hover:bg-primary/30' : 'hover:bg-gray-200' }}">
                <iconify-icon icon="mdi:paper" class="nav-small-cap-icon fs-5"></iconify-icon>
                <p>REPORTS</p>
            </li>
            <li class="p-3 px-5 flex items-center gap-3 hover:bg-gray-200 cursor-pointer {{ request()->routeIs('admin.users.index') ? 'bg-primary/20 text-primary hover:bg-primary/30' : 'hover:bg-gray-200' }}">
                <iconify-icon icon="mdi:account" class="nav-small-cap-icon fs-5"></iconify-icon>
                <p>USERS</p>
            </li>
        </ul>
    </div>


    <div class="w-full flex flex-col gap-2 border-t border-gray-200 py-3 text-sm font-medium">
        <div class="flex items-center gap-2 p-3 px-5 py-0 w-full">
            <iconify-icon icon="mdi:gear" class="nav-small-cap-icon fs-5"></iconify-icon>
            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <x-responsive-nav-link :href="route('logout')"
                    onclick="event.preventDefault();
                                        this.closest('form').submit();">
                    {{ __('SETTINGS') }}
                </x-responsive-nav-link>
            </form>
        </div>

        <div class="flex items-center gap-2 p-3 px-5 py-0 w-full">
            <iconify-icon icon="mdi:logout" class="nav-small-cap-icon fs-5"></iconify-icon>
            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <x-responsive-nav-link :href="route('logout')"
                    onclick="event.preventDefault();
                                        this.closest('form').submit();">
                    {{ __('LOG OUT') }}
                </x-responsive-nav-link>
            </form>
        </div>

        <div class="flex items-center gap-2 p-3 px-5 py-0 w-full">
            <div class="w-8 h-8 bg-blue-900 rounded-md"></div>
            <div>
                <p class="font-bold"> {{ Auth::user()->name }} </p>
                <p class="text-xs"> {{ Auth::user()->role_id == '2' ? 'Admin' : 'Marketing' }} </p>
            </div>
        </div>
    </div>
</nav>
