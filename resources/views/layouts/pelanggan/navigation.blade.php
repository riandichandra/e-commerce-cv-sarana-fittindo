<nav x-data="{ open: false }" class="bg-white border-b border-gray-100 p-3 px-9 flex items-center justify-between">
    <div>
        <h1 class="text-xl font-black">SARANA FITTINDO</h1>
    </div>
    @guest
        <div>
            <form method="POST" action="{{ route('login') }}">
                <x-responsive-nav-link :href="route('login')"
                    onclick="
                                        this.closest('form').submit();">
                    {{ __('Log In') }}
                </x-responsive-nav-link>
            </form>
        </div>
    @endguest

    @auth
        <div class="flex items-center">
            <div class="border w-10 h-10 flex justify-center items-center rounded-full bg-gray-200 cursor-pointer">
                <iconify-icon icon="mdi:user" class="nav-small-cap-icon fs-4"></iconify-icon>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <x-responsive-nav-link :href="route('logout')"
                    onclick="event.preventDefault();
                                        this.closest('form').submit();">
                    {{ __('Log Out') }}
                </x-responsive-nav-link>
            </form>
        </div>
    @endauth

</nav>
