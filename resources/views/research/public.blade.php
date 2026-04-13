<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Public Research Papers | ARCHIVES</title>
    <meta name="description" content="Browse public research papers from Cagayan State University. Login is required only when requesting a PDF download.">
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
</head>
<body class="bg-gray-50 text-gray-800">
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 py-3 sm:py-4 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ url('/') }}" class="flex items-center space-x-3">
                <div class="bg-orange-600 p-2 rounded-lg">
                    <i class="fas fa-book-open text-white"></i>
                </div>
                <span class="font-bold text-gray-900">ARCHIVES</span>
            </a>
            <div class="flex items-center gap-2 ml-auto">
                <a href="{{ route('research.topic-suggestions') }}" class="px-3 sm:px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-100">AI Research Locator</a>
                @if(session('user_id'))
                <a href="{{ route('dashboard') }}" class="px-3 sm:px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-100">Dashboard</a>
                @else
                <a href="{{ route('login') }}" class="px-3 sm:px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-100">Login</a>
                <a href="{{ route('register') }}" class="px-3 sm:px-4 py-2 rounded-lg text-sm font-semibold text-white bg-orange-600 hover:bg-orange-700">Register</a>
                @endif
            </div>
        </div>
    </nav>

    <header class="bg-orange-600 text-white">
        <div class="max-w-7xl mx-auto px-4 py-12">
            <h1 class="text-3xl md:text-4xl font-extrabold">Public Research Papers</h1>
            <p class="mt-3 text-orange-100 max-w-3xl">Explore published research papers from Cagayan State University. You can browse all records publicly, and login is only required when requesting a downloadable PDF.</p>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8">
        <div class="bg-gradient-to-r from-blue-50 to-orange-50 border border-blue-100 rounded-2xl p-5 md:p-6 mb-6 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-blue-700 mb-1">New AI Feature</p>
                <h2 class="text-xl font-bold text-gray-900">Don't know the topic?</h2>
                <p class="text-gray-600 mt-1 max-w-3xl">Use our AI-powered topic suggestions to discover what you are looking for.</p>
            </div>
            <a href="{{ route('research.topic-suggestions') }}" class="inline-flex items-center justify-center bg-orange-600 text-white px-5 py-3 rounded-xl font-semibold hover:bg-orange-700 transition">
                <i class="fas fa-lightbulb mr-2"></i> Open AI Research Locator
            </a>
        </div>

        <form method="GET" action="{{ route('research.public') }}" class="bg-white border border-gray-200 rounded-2xl p-4 md:p-5 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search title, abstract, keywords, authors"
                    class="md:col-span-2 w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:outline-none focus:border-orange-500">

                <select name="college_id" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:outline-none focus:border-orange-500">
                    <option value="">All Colleges</option>
                    @foreach($colleges as $college)
                    <option value="{{ $college->id }}" {{ (string)request('college_id') === (string)$college->id ? 'selected' : '' }}>{{ $college->code }} - {{ $college->name }}</option>
                    @endforeach
                </select>

                <select name="category_id" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:outline-none focus:border-orange-500">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ (string)request('category_id') === (string)$category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>

                <div class="flex flex-col sm:flex-row gap-2">
                    <button type="submit" class="w-full bg-orange-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-orange-700">Filter</button>
                    <a href="{{ route('research.public') }}" class="w-full text-center bg-gray-100 text-gray-700 px-4 py-2.5 rounded-xl font-semibold hover:bg-gray-200">Reset</a>
                </div>
            </div>
        </form>

        @if($research->count())
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            @foreach($research as $item)
            <article class="bg-white border border-gray-200 rounded-2xl p-5 flex flex-col">
                <div class="flex items-center justify-between gap-2 mb-3">
                    <span class="text-xs font-bold bg-orange-100 text-orange-700 px-2.5 py-1 rounded">{{ $item->category->name ?? 'Uncategorized' }}</span>
                    <span class="text-xs text-gray-500">{{ $item->publication_year }}</span>
                </div>

                <h2 class="text-lg font-bold text-gray-900 leading-snug mb-2 line-clamp-2">{{ $item->title }}</h2>
                <p class="text-sm text-gray-600 mb-3 line-clamp-3">{{ $item->abstract }}</p>

                <div class="text-xs text-gray-500 space-y-1 mb-4">
                    <p><span class="font-semibold text-gray-700">College:</span> {{ $item->college->code ?? 'N/A' }}</p>
                    <p><span class="font-semibold text-gray-700">Authors:</span> {{ $item->authors }}</p>
                </div>

                <div class="mt-auto flex gap-2">
                    <a href="{{ route('research.public-show', $item->id) }}" class="w-full text-center bg-orange-600 text-white text-sm font-semibold py-2.5 rounded-lg hover:bg-orange-700">View Research</a>
                </div>
            </article>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $research->links() }}
        </div>
        @else
        <div class="bg-white border border-gray-200 rounded-2xl p-12 text-center">
            <i class="fas fa-folder-open text-4xl text-gray-300 mb-3"></i>
            <h3 class="text-lg font-bold text-gray-800">No research papers found</h3>
            <p class="text-gray-500 mt-1">Try adjusting your search or filters.</p>
        </div>
        @endif
    </main>
</body>
</html>
