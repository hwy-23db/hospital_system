<div x-data="{ open: false }" class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <div @mouseenter="open = true" @mouseleave="open = false"
         class="fixed inset-y-0 left-0 bg-blue-600 text-white flex flex-col transition-all duration-300 z-50"
         :class="open ? 'w-64' : 'w-16'">

        <!-- Brand Header -->
        <div class="h-16 flex items-center justify-between border-b border-blue-500 px-3">
            <a href="{{ route('dashboard') }}" class="text-xl font-semibold" x-show="open">
                {{ config('app.name') }}
            </a>
            <i class="fas fa-bars md:hidden"></i>
        </div>

        <!-- Sidebar Navigation -->
        <nav class="flex-1 px-2 py-6 space-y-2 overflow-y-auto">

            <!-- Dashboard -->
            <a href="{{ route('dashboard') }}"
               class="flex items-center px-3 py-2 rounded hover:bg-blue-500 relative group">
                <i class="fas fa-home w-5 text-center"></i>
                <span x-show="open" class="ml-3 transition-all duration-200">Dashboard</span>

                <!-- Tooltip -->
                <span x-show="!open"
                    class="absolute left-full top-1/2 -translate-y-1/2 ml-2 whitespace-nowrap
                           bg-blue-500 px-2 py-1 rounded text-sm opacity-0 group-hover:opacity-100 transition-opacity">
                    Dashboard
                </span>
            </a>

            <!-- Settings -->
            <a href="#"
               class="flex items-center px-3 py-2 rounded hover:bg-blue-500 relative group">
                <i class="fas fa-cog w-5 text-center"></i>
                <span x-show="open" class="ml-3 transition-all duration-200">Settings</span>

                <span x-show="!open"
                    class="absolute left-full top-1/2 -translate-y-1/2 ml-2 whitespace-nowrap
                           bg-blue-500 px-2 py-1 rounded text-sm opacity-0 group-hover:opacity-100 transition-opacity">
                    Settings
                </span>
            </a>

            <!-- Custom Menu -->
            <a href="#"
               class="flex items-center px-3 py-2 rounded hover:bg-blue-500 relative group">
                <i class="fas fa-star w-5 text-center"></i>
                <span x-show="open" class="ml-3 transition-all duration-200">Anything</span>

                <span x-show="!open"
                    class="absolute left-full top-1/2 -translate-y-1/2 ml-2 whitespace-nowrap
                           bg-blue-500 px-2 py-1 rounded text-sm opacity-0 group-hover:opacity-100 transition-opacity">
                    Anything
                </span>
            </a>

        </nav>

        <!-- Footer User Profile -->
        <div class="border-t border-blue-500 px-4 py-4 flex flex-col items-center"
             :class="open ? 'items-start' : 'items-center'">

            <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
            </div>

            <div x-show="open" class="ml-3 mt-2">
                <div class="font-semibold">{{ Auth::user()->name }}</div>
                <div class="text-sm text-blue-200">{{ Auth::user()->email }}</div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div :class="open ? 'ml-64' : 'ml-16'" class="flex-1 transition-all duration-300">
        @yield('content')
    </div>
</div>

<script src="//unpkg.com/alpinejs" defer></script>
