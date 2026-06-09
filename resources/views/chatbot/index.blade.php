@extends('layouts.app')

@section('title', 'AI Assistant | ARCHIVES')
@section('page-title', 'AI Assistant')
@section('page-subtitle', 'The assistant now opens from the bottom-right corner on every page.')

@section('content')
<div class="mx-auto max-w-4xl">
    <section class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">
        <div class="bg-gradient-to-r from-orange-600 to-blue-600 px-6 py-8 text-white sm:px-8">
            <div>
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/15 text-white">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-orange-100">ARCHI</p>
                        <h3 class="mt-1 text-2xl font-bold">ARCHIVES AI Assistant</h3>
                        <p class="mt-2 max-w-2xl text-sm text-orange-50">The chatbot is available as a floating modal and only answers questions about the ARCHIVES system, archived research, and research-writing support.</p>
                    </div>
                </div>
            </div>
            <div class="mt-6 flex flex-wrap items-center gap-3">
                <button type="button" data-chatbot-open class="inline-flex items-center gap-2 rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-orange-700 transition hover:bg-orange-50">
                    <i class="fas fa-comments"></i>
                    Open AI Assistant
                </button>
                <span class="inline-flex items-center gap-2 rounded-2xl bg-white/10 px-4 py-3 text-sm font-semibold text-white">
                    <span class="h-2 w-2 rounded-full {{ $chatbotAvailable ? 'bg-emerald-300' : 'bg-red-300' }}"></span>
                    {{ $chatbotAvailable ? 'Ollama is available' : 'Ollama is currently unavailable' }}
                </span>
            </div>
        </div>

        <div class="space-y-6 px-6 py-8 sm:px-8">
            <div class="space-y-4">
                <div class="rounded-3xl border border-orange-100 bg-orange-50 p-5">
                    <h4 class="text-lg font-bold text-gray-900">How it works now</h4>
                    <p class="mt-2 text-sm leading-6 text-gray-600">Use the round AI Assistant button in the bottom-right corner. The modal keeps your recent conversation, but it only supports ARCHIVES-related questions and research guidance.</p>
                </div>

                <div class="rounded-3xl border border-blue-100 bg-blue-50 p-5">
                    <h4 class="text-lg font-bold text-gray-900">What you can ask</h4>
                    <div class="mt-3 space-y-2 text-sm leading-6 text-gray-600">
                        <p>Find archived papers related to a topic.</p>
                        <p>Ask how to use archive features like search, download requests, approvals, or previews.</p>
                        <p>Get topic refinement ideas before writing a thesis.</p>
                        <p>Ask for plain-language explanations of research sections or methods.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
