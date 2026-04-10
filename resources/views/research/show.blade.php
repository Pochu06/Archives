@extends('layouts.app')
@section('title', $research->title)
@section('page-title', 'Research Details')
@section('page-subtitle', 'Full research paper information')

@section('styles')
<style>
    .tab-btn { border-bottom: 3px solid transparent; transition: all 0.2s; }
    .tab-btn:hover { color: #ea580c; }
    .tab-btn.active { border-bottom-color: #ea580c; color: #ea580c; font-weight: 600; }
    .tab-content { display: none; }
    .tab-content.active { display: block; }
    .content-table { width: 100%; border-collapse: collapse; margin: 12px 0; font-size: 14px; }
    .content-table th, .content-table td { border: 1px solid #d1d5db; padding: 8px 12px; text-align: left; }
    .content-table th { background-color: #f3f4f6; font-weight: 600; color: #374151; text-align: center; }
    .content-table td { color: #4b5563; }
    .content-table tr:hover td { background-color: #f9fafb; }
    .section-content { text-align: justify; margin-bottom: 8px; color: #374151; font-size: 15px; line-height: 1.75; }
    .figure-container { text-align: center; margin: 20px 0; }
    .figure-image { max-width: 100%; border-radius: 8px; border: 1px solid #e5e7eb; margin: 0 auto; display: block; }
    .figure-caption { font-style: italic; font-size: 14px; color: #6b7280; text-align: center; margin-top: 8px; }
</style>
@endsection

@section('content')
<div class="max-w-6xl mx-auto space-y-5">

    {{-- Back + Actions --}}
    <div class="flex items-center justify-between">
        <a href="{{ session('user_id') ? route('research.index') : route('research.public') }}" class="text-orange-600 hover:underline text-sm font-semibold">
            <i class="fas fa-arrow-left mr-1"></i> Back to Archive
        </a>
        <div class="flex gap-2">
            @if(in_array(session('user_role'), ['super_admin', 'admin']) || $research->user_id == session('user_id'))
            <a href="{{ route('research.edit', $research->id) }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition">
                <i class="fas fa-edit mr-1"></i> Edit
            </a>
            @endif
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-6">

        {{-- Main Content --}}
        <div class="flex-1 min-w-0">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

                {{-- Header --}}
                <div class="p-8 pb-0">
                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        <span class="bg-orange-600 text-white text-xs font-bold px-3 py-1 rounded">{{ $research->category->name ?? 'Uncategorized' }}</span>
                        <span class="border border-green-500 text-green-700 text-xs font-semibold px-3 py-1 rounded">PDF Available</span>
                    </div>

                    <h1 class="text-2xl font-bold text-gray-900 leading-tight mb-4">{{ $research->title }}</h1>

                    <div class="text-sm text-gray-500 space-y-1 mb-4">
                        <p>{{ $research->publication_year }} &middot; {{ $research->college->name ?? 'N/A' }}</p>
                        <p>Archived: {{ $research->created_at->format('F Y') }}</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-x-1 text-sm mb-6">
                        @foreach(explode(',', $research->authors) as $i => $author)
                            @if($i > 0)<span class="text-gray-400">&middot;</span>@endif
                            <span class="text-orange-700 font-medium hover:underline cursor-default">{{ trim($author) }}</span>
                        @endforeach
                    </div>
                </div>

                {{-- Tab Navigation --}}
                <div class="border-b border-gray-200 px-8">
                    <nav class="flex gap-6 -mb-px">
                        <button class="tab-btn active pb-3 text-sm" data-tab="overview">Overview</button>
                        <button class="tab-btn pb-3 text-sm text-gray-500" data-tab="references">References</button>
                        <button class="tab-btn pb-3 text-sm text-gray-500" data-tab="details">Details</button>
                    </nav>
                </div>

                {{-- Tab: Overview --}}
                <div id="tab-overview" class="tab-content active p-8 space-y-8">
                    @if(!empty($aiSummary['summary']))
                    <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shrink-0">
                                <i class="fas fa-wand-magic-sparkles"></i>
                            </div>
                            <div>
                                <div class="flex flex-wrap items-center gap-2 mb-2">
                                    <h3 class="text-lg font-bold text-gray-900">AI Summary</h3>
                                    {{-- <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ ($aiSummary['source'] ?? null) === 'ollama' ? 'bg-blue-100 text-blue-700' : 'bg-gray-200 text-gray-700' }}">
                                        {{ ($aiSummary['source'] ?? null) === 'ollama' ? 'Qwen Model' : 'Quick Fallback' }}
                                    </span> --}}
                                </div>
                                <p class="text-gray-700 leading-relaxed text-[15px]">{{ $aiSummary['summary'] }}</p>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div>
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Abstract</h3>
                        <p class="text-gray-700 leading-relaxed text-[15px]">{{ $research->abstract }}</p>
                    </div>

                    @if($research->keywords)
                    <div>
                        <p class="text-sm text-gray-500 mb-2">Keywords:</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach(explode(',', $research->keywords) as $kw)
                            <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm">{{ trim($kw) }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Conclusion Preview --}}
                    {{-- @if($research->conclusion)
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Conclusion</h3>
                        <p class="text-gray-700 leading-relaxed text-[15px]">{{ $research->conclusion }}</p>
                    </div>
                    @endif

                    @if($research->recommendations)
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Recommendations</h3>
                        <p class="text-gray-700 leading-relaxed text-[15px]">{{ $research->recommendations }}</p>
                    </div>
                    @endif --}}
                </div>

                {{-- Tab: References --}}
                <div id="tab-references" class="tab-content p-8">
                    @if($research->references)
                    <h4 class="font-bold text-gray-900 mb-4">References</h4>
                    <div class="space-y-3">
                        @foreach(preg_split('/\r?\n/', $research->references) as $ref)
                            @if(trim($ref))
                            <p class="text-gray-700 text-[15px] leading-relaxed" style="padding-left: 2rem; text-indent: -2rem;">{{ trim($ref) }}</p>
                            @endif
                        @endforeach
                    </div>
                    @else
                    <p class="text-gray-500">No references listed.</p>
                    @endif
                </div>

                {{-- Tab: Details --}}
                <div id="tab-details" class="tab-content p-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <h4 class="font-bold text-gray-900">Publication Information</h4>
                            <dl class="space-y-3 text-sm">
                                <div class="flex justify-between border-b border-gray-100 pb-2">
                                    <dt class="text-gray-500">College</dt>
                                    <dd class="font-semibold text-gray-800">{{ $research->college->name ?? 'N/A' }}</dd>
                                </div>
                                <div class="flex justify-between border-b border-gray-100 pb-2">
                                    <dt class="text-gray-500">Category</dt>
                                    <dd class="font-semibold text-gray-800">{{ $research->category->name ?? 'N/A' }}</dd>
                                </div>
                                <div class="flex justify-between border-b border-gray-100 pb-2">
                                    <dt class="text-gray-500">Publication Year</dt>
                                    <dd class="font-semibold text-gray-800">{{ $research->publication_year }}</dd>
                                </div>
                                <div class="flex justify-between border-b border-gray-100 pb-2">
                                    <dt class="text-gray-500">Archived by</dt>
                                    <dd class="font-semibold text-gray-800">{{ $research->user->name ?? 'N/A' }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">Date Archived</dt>
                                    <dd class="font-semibold text-gray-800">{{ $research->created_at->format('M d, Y') }}</dd>
                                </div>
                            </dl>
                        </div>
                        <div class="space-y-4">
                            <h4 class="font-bold text-gray-900">Authors</h4>
                            <ul class="space-y-2">
                                @foreach(explode(',', $research->authors) as $author)
                                <li class="flex items-center gap-2 text-sm">
                                    <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center text-orange-700 font-bold text-xs">
                                        {{ strtoupper(substr(trim($author), 0, 1)) }}
                                    </div>
                                    <span class="text-gray-800">{{ trim($author) }}</span>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="w-full lg:w-72 shrink-0 space-y-4">
            {{-- Download / Request Card --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                @if(!session('user_id'))
                    <a href="{{ route('login') }}" class="block w-full bg-orange-600 text-white py-3 rounded-xl font-bold hover:bg-orange-700 transition text-sm text-center">
                        <i class="fas fa-sign-in-alt mr-1"></i> Login to Request Download
                    </a>
                    <p class="text-xs text-gray-500 text-center mt-2">Guests can view the paper, but login is required for download requests.</p>
                @elseif($canDownload)
                    <a href="{{ route('research.download', $research->id) }}" class="block w-full bg-orange-600 text-white py-3 rounded-xl font-bold hover:bg-orange-700 transition text-sm text-center">
                        <i class="fas fa-download mr-1"></i> Download Full-Text PDF
                    </a>
                    <p class="text-xs text-gray-500 text-center mt-2">Auto-generated from archived data</p>
                @elseif($downloadRequest && $downloadRequest->status === 'pending')
                    <div class="text-center">
                        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-3">
                            <i class="fas fa-clock text-yellow-600 text-xl mb-2"></i>
                            <p class="text-sm font-semibold text-yellow-800">Request Pending</p>
                            <p class="text-xs text-yellow-600 mt-1">Waiting for RDE Office approval</p>
                        </div>
                    </div>
                @elseif($downloadRequest && $downloadRequest->status === 'rejected')
                    <div class="text-center mb-3">
                        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-3">
                            <i class="fas fa-times-circle text-red-500 text-xl mb-2"></i>
                            <p class="text-sm font-semibold text-red-800">Request Rejected</p>
                            @if($downloadRequest->rejection_reason)
                            <p class="text-xs text-red-600 mt-1">{{ $downloadRequest->rejection_reason }}</p>
                            @endif
                        </div>
                    </div>
                    <button onclick="document.getElementById('requestModal').classList.remove('hidden')" class="block w-full bg-orange-600 text-white py-3 rounded-xl font-bold hover:bg-orange-700 transition text-sm text-center cursor-pointer">
                        <i class="fas fa-paper-plane mr-1"></i> Request Again
                    </button>
                @else
                    <button onclick="document.getElementById('requestModal').classList.remove('hidden')" class="block w-full bg-orange-600 text-white py-3 rounded-xl font-bold hover:bg-orange-700 transition text-sm text-center cursor-pointer">
                        <i class="fas fa-paper-plane mr-1"></i> Request Download
                    </button>
                    <p class="text-xs text-gray-500 text-center mt-2">Requires approval from RDE Office</p>
                @endif
            </div>

            {{-- Info Card --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-4">
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold mb-1">College</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $research->college->code ?? 'N/A' }}</p>
                    <p class="text-xs text-gray-500">{{ $research->college->name ?? '' }}</p>
                </div>
                <div class="border-t border-gray-100 pt-3">
                    <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold mb-1">Category</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $research->category->name ?? 'N/A' }}</p>
                </div>
                <div class="border-t border-gray-100 pt-3">
                    <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold mb-1">Year</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $research->publication_year }}</p>
                </div>
                <div class="border-t border-gray-100 pt-3">
                    <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold mb-1">Archived by</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $research->user->name ?? 'N/A' }}</p>
                </div>
                <div class="border-t border-gray-100 pt-3">
                    <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold mb-1">Date Archived</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $research->created_at->format('F d, Y') }}</p>
                </div>
            </div>

            {{-- Keywords Card --}}
            @if($research->keywords)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold mb-3">Keywords</p>
                <div class="flex flex-wrap gap-1.5">
                    @foreach(explode(',', $research->keywords) as $kw)
                    <span class="bg-gray-100 text-gray-600 px-2.5 py-1 rounded text-xs">{{ trim($kw) }}</span>
                    @endforeach
                </div>
            </div>
            @endif

            @if(!empty($relatedResearch['items']))
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold mb-1">Related Research</p>
                        <p class="text-sm text-gray-600">Suggested from matching keywords and similar abstract content</p>
                    </div>
                </div>

                <div class="space-y-3">
                    @foreach($relatedResearch['items'] as $item)
                    <a href="{{ session('user_id') ? route('research.show', $item['research']->id) : route('research.public-show', $item['research']->id) }}" class="block rounded-xl border border-gray-200 p-4 hover:border-orange-300 hover:bg-orange-50 transition">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-gray-900 leading-snug">{{ $item['research']->title }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ $item['research']->publication_year }} &middot; {{ $item['research']->category->name ?? 'Uncategorized' }}</p>
                            </div>
                            <i class="fas fa-arrow-right text-gray-400 mt-1"></i>
                        </div>
                        <p class="text-sm text-gray-600 mt-3">{{ $item['reason'] }}</p>
                        @if(!empty($item['shared_keywords']))
                        <div class="flex flex-wrap gap-1.5 mt-3">
                            @foreach($item['shared_keywords'] as $sharedKeyword)
                            <span class="bg-blue-50 text-blue-700 px-2 py-1 rounded text-[11px] font-medium">{{ $sharedKeyword }}</span>
                            @endforeach
                        </div>
                        @endif
                    </a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
    });
});
</script>

{{-- Request Download Modal --}}
@if(session('user_id') && !$canDownload)
<div id="requestModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="bg-white rounded-2xl shadow-xl max-w-md w-full mx-4 overflow-hidden">
        <div class="bg-orange-600 p-5 text-white">
            <h3 class="font-bold text-lg"><i class="fas fa-paper-plane mr-2"></i>Request Download</h3>
            <p class="text-orange-100 text-sm mt-1">Your request will be reviewed by the RDE Office</p>
        </div>
        <form action="{{ route('download-request.store', $research->id) }}" method="POST" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-gray-700 font-semibold mb-2 text-sm">Purpose of Download <span class="text-red-500">*</span></label>
                <textarea name="purpose" rows="3" required placeholder="Please describe why you need to download this paper..."
                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500 text-sm"></textarea>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="document.getElementById('requestModal').classList.add('hidden')" class="bg-gray-100 text-gray-700 px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-gray-200 transition">Cancel</button>
                <button type="submit" class="bg-orange-600 text-white px-5 py-2.5 rounded-xl text-sm font-bold hover:bg-orange-700 transition">
                    <i class="fas fa-paper-plane mr-1"></i> Submit Request
                </button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
