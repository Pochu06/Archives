@extends('layouts.app')
@section('title', 'Research Formatting Tutorial')
@section('page-title', 'Research Formatting Tutorial')
@section('page-subtitle', 'Step-by-step guide for writing your paper in the submission form')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="bg-white border border-gray-200 rounded-2xl p-6 md:p-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">How To Format Your Research Paper</h1>
                <p class="text-gray-600 mt-2">Use this guide while filling out the submission form so your content is clean and easy to read in the generated PDF.</p>
            </div>
            <a href="{{ route('research.create') }}" class="inline-flex items-center justify-center bg-orange-600 text-white px-5 py-2.5 rounded-lg font-semibold hover:bg-orange-700 transition">
                <i class="fas fa-arrow-left mr-2"></i> Back to Submission Form
            </a>
        </div>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-2xl p-5 md:p-6">
        <h2 class="text-lg font-bold text-blue-900 mb-3"><i class="fas fa-lightbulb mr-2"></i>Before You Start</h2>
        <ul class="list-disc pl-5 text-sm text-blue-900 space-y-2">
            <li>Write one section at a time: Abstract, Introduction, Methodology, Results, Discussion, Conclusion, Recommendations, and References.</li>
            <li>Use simple, complete sentences and avoid very long paragraphs.</li>
            <li>Use the toolbar buttons for bold, italic, underline, tables, and preview.</li>
            <li>For references, put one source per line in APA 7th style.</li>
        </ul>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white border border-gray-200 rounded-2xl p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-3">Toolbar Syntax Cheat Sheet</h3>
            <div class="space-y-3 text-sm">
                <div class="p-3 rounded-lg bg-gray-50 border border-gray-200">
                    <p class="font-semibold text-gray-800">Bold</p>
                    <p class="font-mono text-gray-600">**important finding**</p>
                </div>
                <div class="p-3 rounded-lg bg-gray-50 border border-gray-200">
                    <p class="font-semibold text-gray-800">Italic</p>
                    <p class="font-mono text-gray-600">*statistical term*</p>
                </div>
                <div class="p-3 rounded-lg bg-gray-50 border border-gray-200">
                    <p class="font-semibold text-gray-800">Underline</p>
                    <p class="font-mono text-gray-600">__key recommendation__</p>
                </div>
                <div class="p-3 rounded-lg bg-gray-50 border border-gray-200">
                    <p class="font-semibold text-gray-800">Table</p>
                    <p class="font-mono text-gray-600">| Column 1 | Column 2 | Column 3 |</p>
                    <p class="font-mono text-gray-600">| Data | Data | Data |</p>
                </div>
                <div class="p-3 rounded-lg bg-gray-50 border border-gray-200">
                    <p class="font-semibold text-gray-800">Figure Placeholder</p>
                    <p class="font-mono text-gray-600">[figure: filename.png | Figure 1. Caption text]</p>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-3">Section Writing Guide</h3>
            <div class="space-y-3 text-sm text-gray-700">
                <div>
                    <p class="font-semibold text-gray-800">Abstract</p>
                    <p>Summarize the problem, method, key results, and conclusion in one compact paragraph.</p>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">Introduction</p>
                    <p>Explain the background, research gap, and objectives of your study.</p>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">Methodology</p>
                    <p>Describe research design, participants, instruments, and procedures clearly.</p>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">Results and Discussion</p>
                    <p>Present findings first, then explain what they mean and compare with related studies.</p>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">Conclusion and Recommendations</p>
                    <p>State your final answer to the research problem and provide practical next steps.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl p-6 md:p-8">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Sample Content Format</h3>
        <div class="space-y-4 text-sm text-gray-700">
            <div class="p-4 rounded-xl bg-gray-50 border border-gray-200">
                <p class="font-semibold text-gray-900 mb-2">Sample Abstract</p>
                <p>This study examined student performance in programming courses using blended learning. A descriptive-comparative design was used with 120 students. Findings showed improved quiz scores after intervention. The study concludes that blended strategies can improve academic performance when paired with timely feedback.</p>
            </div>

            <div class="p-4 rounded-xl bg-gray-50 border border-gray-200">
                <p class="font-semibold text-gray-900 mb-2">Sample Table</p>
                <pre class="whitespace-pre-wrap font-mono text-xs text-gray-600">| Variable | Mean | Interpretation |
| Attendance | 4.21 | High |
| Quiz Score | 3.98 | High |</pre>
            </div>

            <div class="p-4 rounded-xl bg-gray-50 border border-gray-200">
                <p class="font-semibold text-gray-900 mb-2">Sample APA 7th Reference</p>
                <p class="font-mono text-xs text-gray-600">Dela Cruz, J. P., & Santos, M. R. (2024). Blended learning in tertiary programming courses. Journal of Educational Technology, 15(2), 44-58. https://doi.org/10.1234/jet.2024.0015</p>
            </div>
        </div>
    </div>

    <div class="bg-orange-50 border border-orange-200 rounded-2xl p-5 md:p-6">
        <h3 class="text-lg font-bold text-orange-900 mb-2">Submission Checklist</h3>
        <ul class="list-disc pl-5 text-sm text-orange-900 space-y-1.5">
            <li>All required sections are filled out.</li>
            <li>Title, authors, keywords, college, category, and publication year are complete.</li>
            <li>References are complete and placed one per line.</li>
            <li>Preview was checked before submitting.</li>
        </ul>
        <a href="{{ route('research.create') }}" class="inline-flex items-center mt-4 bg-orange-600 text-white px-5 py-2.5 rounded-lg font-semibold hover:bg-orange-700 transition">
            <i class="fas fa-pen mr-2"></i> Continue Writing My Submission
        </a>
    </div>
</div>
@endsection
