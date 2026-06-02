<x-guest-layout>
    <div class="mb-4 text-center">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </div>
    </div>

    <div class="mb-4 text-center">
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Verification Link Invalid') }}
        </h2>
        <p class="text-sm text-gray-600 mt-2">
            {{ __('The verification link is invalid or has expired. Please request a new verification link.') }}
        </p>
    </div>

    <div class="mt-6">
        <a href="{{ route('login') }}" class="w-full inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            {{ __('Back to Login') }}
        </a>
    </div>
</x-guest-layout>
