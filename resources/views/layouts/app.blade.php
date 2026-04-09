<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'ARCHIVES')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        orange: {
                            50: '#eff6ff', 100: '#dbeafe', 200: '#bfdbfe',
                            300: '#93c5fd', 400: '#60a5fa', 500: '#3b82f6',
                            600: '#2563eb', 700: '#2563eb', 800: '#2563eb', 900: '#2563eb'
                        }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .sidebar-link { transition: all 0.2s ease; }
        .sidebar-link:hover { background: rgba(59,130,246,0.18); padding-left: 1.25rem; }
        .sidebar-link.active { background: rgba(59,130,246,0.25); border-right: 3px solid #3b82f6; }
    </style>
    @yield('styles')
</head>
<body class="bg-gray-50 font-sans">

@if(session('user_id'))
<div class="flex min-h-screen">
    <div id="sidebarOverlay" class="fixed inset-0 bg-black/40 z-20 hidden lg:hidden"></div>

    <!-- Sidebar -->
    <aside id="appSidebar" class="w-64 bg-orange-700 text-white flex flex-col fixed h-full z-30 shadow-xl transform -translate-x-full transition-transform duration-200 lg:translate-x-0">
        <div class="p-5 border-b border-orange-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                <div class="bg-white p-2 rounded-lg">
                    <i class="fas fa-book-open text-orange-600 text-xl"></i>
                </div>
                <div>
                    <h1 class="font-bold text-sm leading-tight">ARCHIVES</h1>
                </div>
                </div>
                <button id="closeSidebarBtn" type="button" class="lg:hidden text-orange-100 hover:text-white" aria-label="Close menu">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
        </div>

        <!-- User Info -->
        <div class="p-4 border-b border-orange-700">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-full bg-orange-500 flex items-center justify-center text-white font-bold text-sm">
                    {{ strtoupper(substr(session('user_name', 'U'), 0, 1)) }}
                </div>
                <div class="overflow-hidden">
                    <p class="font-semibold text-sm truncate">{{ session('user_name') }}</p>
                    <span class="text-xs bg-orange-600 px-2 py-0.5 rounded-full">
                        {{ ucfirst(str_replace('_', ' ', session('user_role'))) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 p-3 space-y-1 overflow-y-auto">
            <a href="{{ route('dashboard') }}" class="sidebar-link flex items-center space-x-3 px-3 py-2.5 rounded-lg text-orange-100 text-sm {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fas fa-tachometer-alt w-5"></i><span>Dashboard</span>
            </a>
            <a href="{{ route('research.index') }}" class="sidebar-link flex items-center space-x-3 px-3 py-2.5 rounded-lg text-orange-100 text-sm {{ request()->routeIs('research.index') || request()->routeIs('research.show') ? 'active' : '' }}">
                <i class="fas fa-archive w-5"></i><span>Research Archive</span>
            </a>
            <a href="{{ route('research.create') }}" class="sidebar-link flex items-center space-x-3 px-3 py-2.5 rounded-lg text-orange-100 text-sm {{ request()->routeIs('research.create') ? 'active' : '' }}">
                <i class="fas fa-plus-circle w-5"></i><span>Archive Paper</span>
            </a>
            @if(!in_array(session('user_role'), ['super_admin', 'admin']))
            <a href="{{ route('download-request.my') }}" class="sidebar-link flex items-center space-x-3 px-3 py-2.5 rounded-lg text-orange-100 text-sm {{ request()->routeIs('download-request.my') ? 'active' : '' }}">
                <i class="fas fa-clipboard-list w-5"></i><span>My Requests</span>
            </a>
            @endif
            @if(in_array(session('user_role'), ['super_admin', 'admin']))
            <div class="pt-3">
                <p class="text-orange-400 text-xs font-semibold uppercase tracking-wider px-3 mb-1">Management</p>
            </div>
            <a href="{{ route('users.index') }}" class="sidebar-link flex items-center space-x-3 px-3 py-2.5 rounded-lg text-orange-100 text-sm {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <i class="fas fa-users w-5"></i><span>User Management</span>
            </a>
            <a href="{{ route('categories.index') }}" class="sidebar-link flex items-center space-x-3 px-3 py-2.5 rounded-lg text-orange-100 text-sm {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                <i class="fas fa-tags w-5"></i><span>Categories</span>
            </a>
            @if(session('user_role') === 'super_admin' || (session('user_role') === 'admin' && !session('user_college_id')))
            <a href="{{ route('download-request.index') }}" class="sidebar-link flex items-center space-x-3 px-3 py-2.5 rounded-lg text-orange-100 text-sm {{ request()->routeIs('download-request.index') ? 'active' : '' }}">
                <i class="fas fa-file-download w-5"></i><span>Download Requests</span>
            </a>
            @endif
            @if(session('user_role') === 'super_admin')
            <a href="{{ route('colleges.index') }}" class="sidebar-link flex items-center space-x-3 px-3 py-2.5 rounded-lg text-orange-100 text-sm {{ request()->routeIs('colleges.*') ? 'active' : '' }}">
                <i class="fas fa-university w-5"></i><span>Colleges</span>
            </a>
            @endif
            @endif
        </nav>

        <!-- Logout -->
        <div class="p-3 border-t border-orange-700">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full flex items-center space-x-3 px-3 py-2.5 rounded-lg text-orange-100 text-sm hover:bg-orange-700 transition">
                    <i class="fas fa-sign-out-alt w-5"></i><span>Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 ml-0 lg:ml-64 min-w-0">
        <header class="bg-white shadow-sm border-b border-gray-200 px-4 sm:px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3 min-w-0">
                    <button id="openSidebarBtn" type="button" class="lg:hidden w-9 h-9 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50" aria-label="Open menu">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-gray-800">@yield('page-title', 'Dashboard')</h2>
                    <p class="text-xs sm:text-sm text-gray-500 truncate">@yield('page-subtitle', '')</p>
                    </div>
                </div>
                <div class="hidden sm:flex items-center space-x-3">
                    <span class="text-sm text-gray-600 text-right">{{ date('l, F j, Y') }}</span>
                </div>
            </div>
        </header>

        <div class="p-4 sm:p-6">
            @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center">
                <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
            </div>
            @endif
            @if(session('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i> {{ session('error') }}
            </div>
            @endif
            @yield('content')
        </div>
    </main>
</div>
@else
    @yield('auth-content')
@endif

<script>
(() => {
    const sidebar = document.getElementById('appSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const openBtn = document.getElementById('openSidebarBtn');
    const closeBtn = document.getElementById('closeSidebarBtn');

    if (!sidebar || !overlay || !openBtn || !closeBtn) return;

    const openSidebar = () => {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
    };

    const closeSidebar = () => {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    };

    openBtn.addEventListener('click', openSidebar);
    closeBtn.addEventListener('click', closeSidebar);
    overlay.addEventListener('click', closeSidebar);

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 1024) {
            overlay.classList.add('hidden');
            sidebar.classList.remove('-translate-x-full');
        } else {
            sidebar.classList.add('-translate-x-full');
        }
    });
})();
</script>

@yield('scripts')
</body>
</html>