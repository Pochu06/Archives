<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Research Archive & Repository System')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        orange: {
                            50: '#fff7ed', 100: '#ffedd5', 200: '#fed7aa',
                            300: '#fdba74', 400: '#fb923c', 500: '#f97316',
                            600: '#ea580c', 700: '#c2410c', 800: '#9a3412', 900: '#7c2d12'
                        }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .sidebar-link { transition: all 0.2s ease; }
        .sidebar-link:hover { background: rgba(234,88,12,0.15); padding-left: 1.25rem; }
        .sidebar-link.active { background: rgba(234,88,12,0.2); border-right: 3px solid #ea580c; }
    </style>
    @yield('styles')
</head>
<body class="bg-gray-50 font-sans">

@if(session('user_id'))
<div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-gradient-to-b from-orange-800 to-orange-900 text-white flex flex-col fixed h-full z-30 shadow-xl">
        <div class="p-5 border-b border-orange-700">
            <div class="flex items-center space-x-3">
                <div class="bg-white p-2 rounded-lg">
                    <i class="fas fa-book-open text-orange-600 text-xl"></i>
                </div>
                <div>
                    <h1 class="font-bold text-sm leading-tight">Research Archive</h1>
                    <p class="text-orange-200 text-xs">& Repository System</p>
                </div>
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
            <a href="{{ route('research.index') }}" class="sidebar-link flex items-center space-x-3 px-3 py-2.5 rounded-lg text-orange-100 text-sm {{ request()->routeIs('research.*') ? 'active' : '' }}">
                <i class="fas fa-file-alt w-5"></i><span>Research Archive</span>
            </a>
            @if(in_array(session('user_role'), ['student']))
            <a href="{{ route('research.create') }}" class="sidebar-link flex items-center space-x-3 px-3 py-2.5 rounded-lg text-orange-100 text-sm">
                <i class="fas fa-plus-circle w-5"></i><span>Submit Research</span>
            </a>
            <a href="{{ route('submissions.index') }}" class="sidebar-link flex items-center space-x-3 px-3 py-2.5 rounded-lg text-orange-100 text-sm {{ request()->routeIs('submissions.*') ? 'active' : '' }}">
                <i class="fas fa-inbox w-5"></i><span>My Submissions</span>
            </a>
            @endif
            @if(session('user_role') === 'adviser')
            <a href="{{ route('adviser.submissions') }}" class="sidebar-link flex items-center space-x-3 px-3 py-2.5 rounded-lg text-orange-100 text-sm {{ request()->routeIs('adviser.submissions') ? 'active' : '' }}">
                <i class="fas fa-tasks w-5"></i><span>Student Submissions</span>
            </a>
            <a href="{{ route('adviser.students') }}" class="sidebar-link flex items-center space-x-3 px-3 py-2.5 rounded-lg text-orange-100 text-sm {{ request()->routeIs('adviser.students') ? 'active' : '' }}">
                <i class="fas fa-user-graduate w-5"></i><span>My Students</span>
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
    <main class="flex-1 ml-64">
        <!-- Top Bar -->
        <header class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">@yield('page-title', 'Dashboard')</h2>
                    <p class="text-sm text-gray-500">@yield('page-subtitle', '')</p>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="text-sm text-gray-600">{{ date('l, F j, Y') }}</span>
                </div>
            </div>
        </header>

        <div class="p-6">
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

@yield('scripts')
</body>
</html>
