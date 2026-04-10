<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Archived Research Finder | ARCHIVES</title>
    <meta name="description" content="Find actual archived research papers using plain-language AI-assisted search.">
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
                <a href="{{ route('research.public') }}" class="px-3 sm:px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-100">Browse Research</a>
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
        <div class="max-w-7xl mx-auto px-4 py-12 md:py-16">
            <div class="max-w-4xl">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-orange-100">AI Archived Research Finder</p>
                <h1 class="text-3xl md:text-5xl font-extrabold mt-3 leading-tight">Find actual archived papers even if you do not know the exact keywords.</h1>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8 md:py-10">
        <div class="grid grid-cols-1 xl:grid-cols-[1.1fr,0.9fr] gap-6 items-start">
            <section class="bg-white border border-gray-200 rounded-3xl p-6 md:p-8 shadow-sm">
                <div class="flex items-start gap-4 mb-6">
                    <div class="w-12 h-12 rounded-2xl bg-blue-600 text-white flex items-center justify-center shrink-0">
                        <i class="fas fa-magnifying-glass"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Find Archived Papers</h2>
                        <p class="text-gray-600 mt-1">Describe your topic in plain language. The system will search real archived papers and use AI to understand related academic terms.</p>
                    </div>
                </div>

                <form method="GET" action="{{ route('research.topic-suggestions') }}" class="space-y-5">
                    <input type="hidden" name="mode" id="suggestion-mode" value="{{ request('mode', 'fast') }}">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Topic, Problem, or Interest</label>
                        <textarea name="interest" rows="4" placeholder="Example: I want papers about student engagement in online classes but I do not know the exact research terms..." class="w-full px-4 py-3 border border-gray-300 rounded-2xl focus:outline-none focus:border-orange-500">{{ request('interest') }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Preferred Category</label>
                            <select name="category_id" class="w-full px-4 py-3 border border-gray-300 rounded-2xl focus:outline-none focus:border-orange-500">
                                <option value="">Any category</option>
                                @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ (string) request('category_id') === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Preferred College</label>
                            <select name="college_id" class="w-full px-4 py-3 border border-gray-300 rounded-2xl focus:outline-none focus:border-orange-500">
                                <option value="">Any college</option>
                                @foreach($colleges as $college)
                                <option value="{{ $college->id }}" {{ (string) request('college_id') === (string) $college->id ? 'selected' : '' }}>{{ $college->code }} - {{ $college->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <button type="submit" onclick="document.getElementById('suggestion-mode').value='fast'" class="bg-orange-600 text-white px-6 py-3 rounded-2xl font-semibold hover:bg-orange-700 transition">
                            <i class="fas fa-bolt mr-2"></i> Quick Search
                        </button>
                        <button type="submit" onclick="document.getElementById('suggestion-mode').value='ai'" class="bg-blue-600 text-white px-6 py-3 rounded-2xl font-semibold hover:bg-blue-700 transition">
                            <i class="fas fa-wand-magic-sparkles mr-2"></i> AI Search
                        </button>
                        <a href="{{ route('research.topic-suggestions') }}" class="bg-gray-100 text-gray-700 px-6 py-3 rounded-2xl font-semibold hover:bg-gray-200 transition text-center">Reset</a>
                    </div>
                    <p class="text-xs text-gray-500">Quick Search uses direct archive matching for speed. AI Search is slower because Qwen first interprets your plain-language description, then searches and reranks actual archived papers.</p>
                </form>
            </section>

            <aside class="bg-white border border-gray-200 rounded-3xl p-6 md:p-8 shadow-sm">
                <h2 class="text-xl font-bold text-gray-900">How This Helps</h2>
                <p class="text-gray-600 mt-2">Students often know the topic they want, but not the exact keywords used in archived papers. This tool helps bridge that gap.</p>
                <div class="space-y-4 mt-6 text-sm text-gray-600">
                    <div class="rounded-2xl bg-orange-50 border border-orange-100 p-4">
                        <p class="font-semibold text-gray-900 mb-1">What it uses</p>
                        <p>Student description, selected college/category, archived papers, title terms, abstracts, and keywords.</p>
                    </div>
                    <div class="rounded-2xl bg-blue-50 border border-blue-100 p-4">
                        <p class="font-semibold text-gray-900 mb-1">What it returns</p>
                        <p>Actual archived papers that best match the topic the student described.</p>
                    </div>
                    <div class="rounded-2xl bg-gray-50 border border-gray-200 p-4">
                        <p class="font-semibold text-gray-900 mb-1">Tip</p>
                        <p>Instead of typing only one keyword, describe the problem or subject in one sentence, like “papers about mobile apps for monitoring fish catch in coastal areas”.</p>
                    </div>
                </div>
            </aside>
        </div>

        @if($errors->any())
        <div class="mt-6 bg-red-50 border border-red-200 text-red-700 rounded-2xl p-4">
            <p class="font-semibold mb-1">Please check your input.</p>
            @foreach($errors->all() as $error)
            <p class="text-sm">{{ $error }}</p>
            @endforeach
        </div>
        @endif

        @if(!empty($suggestions['items']))
        <section class="mt-8 space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Matched Archived Papers</h2>
                    <p class="text-gray-600 mt-1">These are real papers from the archive that match the topic you described.</p>
                </div>
                <span class="text-xs font-semibold px-3 py-1.5 rounded-full {{ ($suggestions['source'] ?? null) === 'ollama' ? 'bg-blue-100 text-blue-700' : 'bg-orange-100 text-orange-700' }}">
                    {{ ($suggestions['source'] ?? null) === 'ollama' ? 'AI Search' : 'Quick Search' }}
                </span>
            </div>

            @if(!empty($suggestions['search_terms']))
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Search Terms Used</p>
                <div class="flex flex-wrap gap-2">
                    @foreach(array_slice($suggestions['search_terms'], 0, 10) as $term)
                    <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-xs font-medium">{{ $term }}</span>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                @foreach($suggestions['items'] as $item)
                <article class="bg-white border border-gray-200 rounded-3xl p-6 shadow-sm">
                    <div class="flex items-start gap-3">
                        <div class="w-11 h-11 rounded-2xl bg-orange-100 text-orange-700 flex items-center justify-center shrink-0">
                            <i class="fas fa-file-lines"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 leading-snug">{{ $item['research']->title }}</h3>
                            <p class="text-xs text-gray-500 mt-2">{{ $item['research']->publication_year }} &middot; {{ $item['research']->category->name ?? 'Uncategorized' }} &middot; {{ $item['research']->college->code ?? 'N/A' }}</p>
                            <p class="text-gray-600 mt-3 leading-relaxed">{{ $item['reason'] }}</p>
                            <p class="text-sm text-gray-600 mt-3">{{ \Illuminate\Support\Str::limit(strip_tags($item['research']->abstract), 180) }}</p>
                        </div>
                    </div>

                    @if(!empty($item['matched_terms']))
                    <div class="mt-5 pt-4 border-t border-gray-100">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Matched Terms</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($item['matched_terms'] as $matchedTerm)
                            <span class="bg-blue-50 text-blue-700 px-3 py-1 rounded-full text-xs font-medium">{{ $matchedTerm }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div class="mt-5 pt-4 border-t border-gray-100">
                        <a href="{{ session('user_id') ? route('research.show', $item['research']->id) : route('research.public-show', $item['research']->id) }}" class="inline-flex items-center text-orange-700 font-semibold hover:text-orange-800">
                            View Archived Paper <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                </article>
                @endforeach
            </div>
        </section>
        @elseif(request()->filled('interest') || request()->filled('category_id') || request()->filled('college_id'))
        <section class="mt-8 bg-white border border-gray-200 rounded-3xl p-10 text-center shadow-sm">
            <i class="fas fa-search text-4xl text-gray-300 mb-4"></i>
            <h2 class="text-xl font-bold text-gray-900">No archived papers were found</h2>
            <p class="text-gray-600 mt-2">Try describing the topic in a different way, or use AI Search so the system can infer related academic keywords for you.</p>
        </section>
        @endif
    </main>
</body>
</html>