<nav x-data="{ open: false }"
    class="bg-white border-b border-gray-100 flex flex-col items-center fixed w-1/5 h-screen justify-between">
    <div>
        <div class="m-5">
            <h1 class="text-xl font-bold">CV SARANA FITTINDO</h1>
            <p class="text-sm">Management Portal</p>
        </div>
        <ul class="w-full mt-3 text-gray-600">
            <li class="p-3 px-5 flex items-center gap-2 hover:bg-gray-200 cursor-pointer bg-gray-300">
                <iconify-icon icon="mdi:home" class="nav-small-cap-icon fs-5"></iconify-icon>
                <p>Dashboard</p>
            </li>
            <li class="p-3 px-5 flex items-center gap-2 hover:bg-gray-200 cursor-pointer">
                <iconify-icon icon="mdi:box-variant" class="nav-small-cap-icon fs-5"></iconify-icon>
                <p>Products</p>
            </li>
            <li class="p-3 px-5 flex items-center gap-2 hover:bg-gray-200 cursor-pointer">
                <iconify-icon icon="mdi:shopping" class="nav-small-cap-icon fs-5"></iconify-icon>
                <p>Orders</p>
            </li>
            <li class="p-3 px-5 flex items-center gap-2 hover:bg-gray-200 cursor-pointer">
                <iconify-icon icon="mdi:money" class="nav-small-cap-icon fs-5"></iconify-icon>
                <p>Payments</p>
            </li>
            <li class="p-3 px-5 flex items-center gap-2 hover:bg-gray-200 cursor-pointer">
                <iconify-icon icon="mdi:loudspeaker" class="nav-small-cap-icon fs-5"></iconify-icon>
                <p>Promotions</p>
            </li>
            <li class="p-3 px-5 flex items-center gap-2 hover:bg-gray-200 cursor-pointer">
                <iconify-icon icon="mdi:paper" class="nav-small-cap-icon fs-5"></iconify-icon>
                <p>Reports</p>
            </li>
            <li class="p-3 px-5 flex items-center gap-2 hover:bg-gray-200 cursor-pointer">
                <iconify-icon icon="mdi:account" class="nav-small-cap-icon fs-5"></iconify-icon>
                <p>Users</p>
            </li>
        </ul>
    </div>


    <div class="w-full flex flex-col gap-2 border-t border-gray-200 pt-3">
        <div class="flex items-center gap-2 p-3 px-5 py-0 w-full">
            <iconify-icon icon="mdi:gear" class="nav-small-cap-icon fs-5"></iconify-icon>
            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <x-responsive-nav-link :href="route('logout')"
                    onclick="event.preventDefault();
                                        this.closest('form').submit();">
                    {{ __('Settings') }}
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
                    {{ __('Log Out') }}
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
