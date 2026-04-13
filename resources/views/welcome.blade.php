<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSU Research Archive | Public Research Repository</title>
    <meta name="description" content="CSU Research Archive is a public research repository where you can browse academic papers by college, category, and year. Login is required only to request PDF downloads.">
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
        .hero-bg { background: #3b82f6; }
        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-white shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 py-3 sm:py-4 flex flex-wrap justify-between items-center gap-3">
            <div class="flex items-center space-x-3">
                <div class="bg-orange-600 p-2.5 rounded-lg">
                    <i class="fas fa-book-open text-white text-xl"></i>
                </div>
                <div>
                    <h1 class="font-bold text-gray-800 text-lg leading-tight">ARCHIVES</h1>
                </div>
            </div>
            @if(session('user_id'))
                <div class="flex items-center space-x-2 sm:space-x-3 ml-auto">
                    <a href="{{ route('dashboard') }}" class="text-gray-700 hover:text-orange-600 font-medium px-3 sm:px-4 py-2 rounded-lg hover:bg-orange-50 transition text-sm sm:text-base">
                        Dashboard
                    </a>
                </div>
            @else
                <div class="flex items-center space-x-2 sm:space-x-3 ml-auto">
                    <a href="{{ route('login') }}" class="text-gray-700 hover:text-orange-600 font-medium px-3 sm:px-4 py-2 rounded-lg hover:bg-orange-50 transition text-sm sm:text-base">
                        <i class="fas fa-sign-in-alt mr-1"></i> Login
                    </a>
                    <a href="{{ route('register') }}" class="bg-orange-600 text-white px-4 sm:px-5 py-2 rounded-lg font-medium hover:bg-orange-700 transition shadow text-sm sm:text-base">
                        <i class="fas fa-user-plus mr-1"></i> Register
                    </a>
                </div>
            @endif
            {{-- <div class="flex items-center space-x-3">
                <a href="{{ route('login') }}" class="text-gray-700 hover:text-orange-600 font-medium px-4 py-2 rounded-lg hover:bg-orange-50 transition">
                    <i class="fas fa-sign-in-alt mr-1"></i> Login
                </a>
                <a href="{{ route('register') }}" class="bg-orange-600 text-white px-5 py-2 rounded-lg font-medium hover:bg-orange-700 transition shadow">
                    <i class="fas fa-user-plus mr-1"></i> Register
                </a>
            </div> --}}
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero-bg text-white py-16 sm:py-20 lg:py-24 px-4 min-h-[72vh]">
        <div class="max-w-5xl mx-auto text-center">
            <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-extrabold mb-6 leading-tight">
                ARCHIVES
            </h1>
            <p class="text-base sm:text-lg md:text-xl text-orange-100 mb-8 sm:mb-10 max-w-3xl mx-auto leading-relaxed">
                Explore a comprehensive repository of academic research papers from Cagayan State University. Browse publicly available research.
            </p>

            <form action="{{ route('research.public') }}" method="GET" class="max-w-3xl mx-auto mb-8">
                <div class="bg-white rounded-2xl p-2 shadow-lg flex flex-col sm:flex-row gap-2">
                    <div class="relative flex-1">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Search research title, abstract, keywords, or authors"
                            class="w-full h-12 pl-11 pr-4 rounded-xl border border-gray-200 text-gray-800 focus:outline-none focus:border-orange-500"
                        >
                    </div>
                    <button type="submit" class="h-12 px-6 rounded-xl bg-orange-600 text-white font-semibold hover:bg-orange-700 transition whitespace-nowrap">
                        Search Research
                    </button>
                </div>
            </form>

            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('research.public') }}" class="bg-white text-orange-700 font-bold px-8 py-4 rounded-xl hover:bg-orange-50 transition shadow-lg text-lg">
                    <i class="fas fa-book-open mr-2"></i> Browse Public Research
                </a>
                <a href="{{ route('research.topic-suggestions') }}" class="bg-orange-900/30 border-2 border-white text-white font-bold px-8 py-4 rounded-xl hover:bg-white/10 transition text-lg">
                    <i class="fas fa-lightbulb mr-2"></i> AI Research Locator
                </a>
                @if(session('user_id'))
                    <a href="{{ route('dashboard') }}" class="border-2 border-white text-white font-bold px-8 py-4 rounded-xl hover:bg-white/10 transition text-lg">
                        Go to Dashboard
                    </a>
                @else
                    <a href="{{ route('register') }}" class="border-2 border-white text-white font-bold px-8 py-4 rounded-xl hover:bg-white/10 transition text-lg">
                        <i class="fas fa-user-graduate mr-2"></i> Join as Student
                    </a>
                @endif
            </div>
        </div>
    </section>

    <!-- College Badges -->
    <section class="bg-orange-800 text-white py-6">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex flex-wrap justify-center gap-4">
                @foreach(['CICS', 'CTED', 'CBEA', 'CFAS', 'CIT', 'CCJE', 'CHM', 'GS'] as $college)
                <span class="bg-white/10 backdrop-blur px-4 py-2 rounded-full text-sm font-semibold">
                    <i class="fas fa-university mr-2 text-orange-300"></i>{{ $college }}
                </span>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Features -->
    {{-- <section class="py-20 px-4 bg-white">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-14">
                <h2 class="text-4xl font-bold text-gray-800 mb-4">Platform Features</h2>
                <p class="text-gray-600 text-lg max-w-2xl mx-auto">Everything you need to manage academic research from submission to publication approval.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="card-hover bg-gradient-to-br from-orange-50 to-orange-100 rounded-2xl p-8 border border-orange-200">
                    <div class="bg-gradient-to-br from-orange-500 to-orange-700 w-14 h-14 rounded-xl flex items-center justify-center mb-6">
                        <i class="fas fa-upload text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Easy Submission</h3>
                    <p class="text-gray-600">Students can easily submit research papers with file uploads, abstracts, keywords, and author information through an intuitive interface.</p>
                </div>
                <div class="card-hover bg-gradient-to-br from-blue-50 to-blue-100 rounded-2xl p-8 border border-blue-200">
                    <div class="bg-gradient-to-br from-blue-500 to-blue-700 w-14 h-14 rounded-xl flex items-center justify-center mb-6">
                        <i class="fas fa-shield-alt text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Role-Based Access</h3>
                    <p class="text-gray-600">Comprehensive access control with Super Admin, Admin, and Student roles — each with tailored permissions and views.</p>
                </div>
                <div class="card-hover bg-gradient-to-br from-green-50 to-green-100 rounded-2xl p-8 border border-green-200">
                    <div class="bg-gradient-to-br from-green-500 to-green-700 w-14 h-14 rounded-xl flex items-center justify-center mb-6">
                        <i class="fas fa-search text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Advanced Search</h3>
                    <p class="text-gray-600">Search and filter research by college, category, year, and keywords. Access the full archive of approved academic research.</p>
                </div>
                <div class="card-hover bg-gradient-to-br from-purple-50 to-purple-100 rounded-2xl p-8 border border-purple-200">
                    <div class="bg-gradient-to-br from-purple-500 to-purple-700 w-14 h-14 rounded-xl flex items-center justify-center mb-6">
                        <i class="fas fa-check-circle text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Approval Workflow</h3>
                    <p class="text-gray-600">Structured process where admins can manage and organize research submissions in the archive.</p>
                </div>
                <div class="card-hover bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-2xl p-8 border border-yellow-200">
                    <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 w-14 h-14 rounded-xl flex items-center justify-center mb-6">
                        <i class="fas fa-chart-bar text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Analytics Dashboard</h3>
                    <p class="text-gray-600">Insightful dashboards showing research statistics, submission trends, and college-wise breakdowns for data-driven decisions.</p>
                </div>
                <div class="card-hover bg-gradient-to-br from-red-50 to-red-100 rounded-2xl p-8 border border-red-200">
                    <div class="bg-gradient-to-br from-red-500 to-red-700 w-14 h-14 rounded-xl flex items-center justify-center mb-6">
                        <i class="fas fa-download text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Document Management</h3>
                    <p class="text-gray-600">Secure document upload and download system supporting PDF and Word files with proper access control and storage.</p>
                </div>
            </div>
        </div>
    </section> --}}

    <!-- Colleges -->
    {{-- <section class="py-20 px-4 bg-orange-50">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-14">
                <h2 class="text-4xl font-bold text-gray-800 mb-4">Participating Colleges</h2>
                <p class="text-gray-600 text-lg">Seven colleges united under one research management platform.</p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                @php
                    $colleges = [
                        ['code' => 'CICS', 'name' => 'Information & Computing Sciences', 'icon' => 'fa-laptop-code', 'color' => 'bg-blue-600'],
                        ['code' => 'CTED', 'name' => 'Teacher Education', 'icon' => 'fa-chalkboard-teacher', 'color' => 'bg-green-600'],
                        ['code' => 'CBEA', 'name' => 'Business, Entrepreneurship & Accountancy', 'icon' => 'fa-briefcase', 'color' => 'bg-yellow-600'],
                        ['code' => 'CFAS', 'name' => 'Fisheries & Aquatic Sciences', 'icon' => 'fa-fish', 'color' => 'bg-cyan-600'],
                        ['code' => 'CIT', 'name' => 'Industrial Technology', 'icon' => 'fa-cogs', 'color' => 'bg-orange-600'],
                        ['code' => 'CCJE', 'name' => 'Criminal Justice Education', 'icon' => 'fa-balance-scale', 'color' => 'bg-red-600'],
                        ['code' => 'CHM', 'name' => 'Hospitality Management', 'icon' => 'fa-h-square', 'color' => 'bg-pink-600'],
                    ];
                @endphp
                @foreach($colleges as $college)
                <div class="card-hover bg-white rounded-2xl p-6 shadow-sm border border-orange-100 text-center">
                    <div class="{{ $college['color'] }} w-14 h-14 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas {{ $college['icon'] }} text-white text-xl"></i>
                    </div>
                    <h3 class="font-bold text-orange-800 text-xl mb-1">{{ $college['code'] }}</h3>
                    <p class="text-gray-600 text-xs">{{ $college['name'] }}</p>
                </div>
                @endforeach
                <div class="card-hover bg-orange-700 rounded-2xl p-6 shadow-sm text-center text-white">
                    <div class="bg-white/20 w-14 h-14 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-plus text-white text-xl"></i>
                    </div>
                    <h3 class="font-bold text-xl mb-1">Expanding</h3>
                    <p class="text-orange-200 text-xs">More colleges coming soon</p>
                </div>
            </div>
        </div>
    </section> --}}

    <!-- CTA -->
    {{-- <section class="hero-bg py-20 px-4 text-white">
        <div class="max-w-3xl mx-auto text-center">
            <h2 class="text-4xl font-bold mb-6">Ready to Archive Your Research?</h2>
            <p class="text-orange-100 text-lg mb-8">Join thousands of students and researchers contributing to our growing academic repository.</p>
            <a href="{{ route('register') }}" class="bg-white text-orange-700 font-bold px-10 py-4 rounded-xl hover:bg-orange-50 transition shadow-lg text-lg inline-block">
                <i class="fas fa-rocket mr-2"></i> Get Started Today
            </a>
        </div>
    </section> --}}

    <!-- Footer -->
    <footer class="bg-orange-900 text-white">
        <div class="max-w-7xl mx-auto px-4 py-12 grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
                <div class="flex items-center space-x-3 mb-4">
                    <div class="bg-orange-600 p-2 rounded-lg"><i class="fas fa-book-open"></i></div>
                    <h3 class="font-bold">ARCHIVES</h3>
                </div>
                <p class="text-orange-200 text-sm">A centralized research management platform serving multiple colleges.</p>
            </div>
            <div>
                <h4 class="font-bold mb-3">Quick Links</h4>
                <ul class="space-y-2 text-orange-200 text-sm">
                    <li><a href="{{ route('login') }}" class="hover:text-white transition">Login</a></li>
                    <li><a href="{{ route('register') }}" class="hover:text-white transition">Register</a></li>
                </ul>
            </div>
            {{-- <div>
                <h4 class="font-bold mb-3">Colleges</h4>
                <ul class="space-y-2 text-orange-200 text-sm">
                    @foreach(['CICS', 'CTED', 'CBEA', 'CFAS'] as $c)
                    <li>{{ $c }}</li>
                    @endforeach
                </ul>
            </div>
            <div>
                <h4 class="font-bold mb-3">More Colleges</h4>
                <ul class="space-y-2 text-orange-200 text-sm">
                    @foreach(['CIT', 'CCJE', 'CHM'] as $c)
                    <li>{{ $c }}</li>
                    @endforeach
                </ul>
            </div> --}}
        </div>
        <div class="border-t border-orange-800 py-6 text-center text-sm text-orange-300">
            <p>© {{ date('Y') }} ARCHIVES. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
