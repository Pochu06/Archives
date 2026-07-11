@extends('layouts.app')
@section('title', session('user_role') === 'student' ? 'Submit Research Paper' : 'Archive Research Paper')
@section('page-title', session('user_role') === 'student' ? 'Submit Research Paper' : 'Archive Research Paper')
@section('page-subtitle', session('user_role') === 'student' ? 'Submit your final IMRAD paper for college and RDE approval' : 'Add a final IMRAD research paper to the archive')

@section('styles')
<style>
    .fmt-btn {
        background: white; border: 1px solid #e5e7eb; border-radius: 6px;
        padding: 4px 10px; font-size: 13px; color: #6b7280; cursor: pointer;
        transition: all 0.15s; display: inline-flex; align-items: center; gap: 4px;
    }
    .fmt-btn:hover { background: #fff7ed; border-color: #fb923c; color: #ea580c; }
    .fmt-btn.active { background: #fff7ed; border-color: #ea580c; color: #ea580c; }
    .fmt-textarea { border-top-left-radius: 0 !important; border-top-right-radius: 0 !important; }
    .fmt-preview {
        border: 2px solid #e5e7eb; border-top: none; border-radius: 0 0 12px 12px;
        padding: 16px; background: #fafafa; min-height: 60px; font-size: 15px;
        line-height: 1.75; color: #374151;
    }
    .fmt-preview .section-content { text-align: justify; margin-bottom: 8px; }
    .fmt-preview .content-table { width: 100%; border-collapse: collapse; margin: 12px 0; font-size: 14px; }
    .fmt-preview .content-table th, .fmt-preview .content-table td { border: 1px solid #d1d5db; padding: 8px 12px; text-align: left; }
    .fmt-preview .content-table th { background: #f3f4f6; font-weight: 600; text-align: center; }
    .fmt-preview .content-table.table-style-classic { border: 1px solid #d1d5db; }
    .fmt-preview .content-table.table-style-classic th { background: #f3f4f6; color: #111827; }
    .fmt-preview .content-table.table-style-striped { border: 1px solid #e5e7eb; }
    .fmt-preview .content-table.table-style-striped th { background: #fff7ed; color: #9a3412; border-color: #fed7aa; }
    .fmt-preview .content-table.table-style-striped td { border-color: #e5e7eb; }
    .fmt-preview .content-table.table-style-striped tbody tr:nth-child(even) td { background: #f9fafb; }
    .fmt-preview .content-table.table-style-minimal { border-collapse: separate; border-spacing: 0; }
    .fmt-preview .content-table.table-style-minimal th,
    .fmt-preview .content-table.table-style-minimal td { border: none; border-bottom: 1px solid #e5e7eb; padding: 10px 12px; }
    .fmt-preview .content-table.table-style-minimal th { background: transparent; color: #374151; text-transform: uppercase; font-size: 12px; letter-spacing: 0.03em; }
    .fmt-preview .figure-container { text-align: center; margin: 16px 0; }
    .fmt-preview .figure-image { max-width: 100%; border-radius: 8px; border: 1px solid #e5e7eb; margin: 0 auto; display: block; }
    .fmt-preview .figure-caption { font-style: italic; font-size: 14px; color: #6b7280; text-align: center; margin-top: 8px; }
    .save-status { position: fixed; top-20 right-4 z-50; padding: 12px 16px; border-radius: 8px; font-size: 13px; font-weight: 500; transition: opacity 0.3s; }
    .save-status.saving { background: #fef3c7; border: 1px solid #fcd34d; color: #92400e; display: flex; align-items: center; gap: 8px; }
    .save-status.saved { background: #dcfce7; border: 1px solid #86efac; color: #166534; display: flex; align-items: center; gap: 8px; }
    .save-status.error { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; display: flex; align-items: center; gap: 8px; }
    .spinner { width: 12px; height: 12px; border: 2px solid transparent; border-top-color: currentColor; border-radius: 50%; animation: spin 0.6s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }
</style>
@endsection

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Draft Status Indicator -->
    <div id="saveStatus" class="save-status"></div>

    <!-- Draft Recovery Modal -->
    @if($draft)
    <div id="draftModal" class="fixed inset-0 bg-black bg-opacity-50 z-40 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-lg max-w-md w-full p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-history text-blue-600"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-800">Draft Found</h3>
            </div>
            <p class="text-gray-600 text-sm mb-2">We found a draft saved on <strong>{{ $draft->last_saved_at->format('M d, Y h:i A') }}</strong></p>
            <p class="text-gray-500 text-xs mb-6">Would you like to continue with this draft or start fresh?</p>
            <div class="flex gap-3">
                <button type="button" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-semibold hover:bg-gray-200 transition" onclick="startFresh()">
                    Start Fresh
                </button>
                <button type="button" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition" onclick="loadDraftData()">
                    <i class="fas fa-redo mr-2"></i> Continue Draft
                </button>
            </div>
        </div>
    </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-orange-600 to-orange-800 p-6 text-white">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold"><i class="fas fa-file-upload mr-2"></i>{{ session('user_role') === 'student' ? 'Submit Final Research Paper' : 'Archive Final Research Paper' }}</h2>
                    <p class="text-orange-100 text-sm mt-1">{{ session('user_role') === 'student' ? 'Fill in the IMRAD sections and submit it for college approval, then RDE approval.' : 'Fill in the IMRAD sections. A PDF will be generated automatically.' }}</p>
                </div>
                <a href="{{ route('research.tutorial') }}" class="inline-flex items-center justify-center sm:justify-start bg-white text-orange-700 text-sm font-bold px-4 py-2 rounded-lg hover:bg-orange-50 transition">
                    <i class="fas fa-book-open mr-2"></i> Formatting Tutorial
                </a>
            </div>
        </div>
        <form action="{{ route('research.store') }}" method="POST" id="researchForm" class="p-8 space-y-6">
            @csrf
            <input type="hidden" name="table_design" id="tableDesignInput" value="{{ old('table_design') }}">

            {{-- Title --}}
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Research Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" value="{{ old('title') }}" placeholder="Enter the full title of the research paper..."
                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500 @error('title') border-red-400 @enderror">
                @error('title')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Authors & Keywords --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Authors <span class="text-red-500">*</span></label>
                    <input type="text" name="authors" value="{{ old('authors') }}" placeholder="Juan Dela Cruz, Maria Santos..."
                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500 @error('authors') border-red-400 @enderror">
                    @error('authors')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Keywords <span class="text-red-500">*</span></label>
                    <input type="text" name="keywords" value="{{ old('keywords') }}" placeholder="machine learning, AI, education..."
                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500 @error('keywords') border-red-400 @enderror">
                    @error('keywords')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Abstract --}}
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Abstract <span class="text-red-500">*</span></label>
                @include('research.partials.formatter-toolbar', ['target' => 'abstract'])
                <textarea name="abstract" id="abstract" rows="4" placeholder="Write a concise summary of the research paper..."
                    class="fmt-textarea w-full px-4 py-3 border-2 border-gray-200 rounded-b-xl rounded-t-none focus:outline-none focus:border-orange-500 @error('abstract') border-red-400 @enderror">{{ old('abstract') }}</textarea>
                <div id="preview-abstract" class="fmt-preview hidden"></div>
                @error('abstract')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- IMRAD Sections --}}
            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-lg font-bold text-gray-800 mb-1"><i class="fas fa-layer-group text-orange-500 mr-2"></i>IMRAD Sections</h3>
                <p class="text-sm text-gray-500 mb-4">Provide the content for each section of the paper.</p>
            </div>

            {{-- Image Upload Section --}}
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-5 mb-2">
                <h4 class="font-semibold text-gray-700 mb-2"><i class="fas fa-image text-orange-500 mr-1"></i> Upload Figures</h4>
                <p class="text-xs text-gray-500 mb-3">Upload images first, then paste the generated syntax into any section below.</p>
                <div class="flex items-center gap-3">
                    <label class="cursor-pointer bg-white border-2 border-dashed border-gray-300 rounded-xl px-5 py-3 hover:border-orange-400 transition flex items-center gap-2 text-sm text-gray-600">
                        <i class="fas fa-cloud-upload-alt text-orange-500"></i> Choose Image
                        <input type="file" id="figureUpload" accept="image/*" class="hidden">
                    </label>
                    <span id="uploadStatus" class="text-sm text-gray-400"></span>
                </div>
                <div id="uploadedFigures" class="mt-3 space-y-2 hidden">
                    <p class="text-xs font-semibold text-gray-600 mb-1">Uploaded figures — copy the syntax and paste into any section:</p>
                </div>
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Introduction <span class="text-red-500">*</span></label>
                @include('research.partials.formatter-toolbar', ['target' => 'introduction'])
                <textarea name="introduction" id="introduction" rows="5" placeholder="Background of the study, statement of the problem, significance..."
                    class="fmt-textarea w-full px-4 py-3 border-2 border-gray-200 rounded-b-xl rounded-t-none focus:outline-none focus:border-orange-500 @error('introduction') border-red-400 @enderror">{{ old('introduction') }}</textarea>
                <div id="preview-introduction" class="fmt-preview hidden"></div>
                @error('introduction')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Methodology <span class="text-red-500">*</span></label>
                @include('research.partials.formatter-toolbar', ['target' => 'methodology'])
                <textarea name="methodology" id="methodology" rows="5" placeholder="Research design, respondents, instruments, data gathering procedures..."
                    class="fmt-textarea w-full px-4 py-3 border-2 border-gray-200 rounded-b-xl rounded-t-none focus:outline-none focus:border-orange-500 @error('methodology') border-red-400 @enderror">{{ old('methodology') }}</textarea>
                <div id="preview-methodology" class="fmt-preview hidden"></div>
                @error('methodology')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Results and Discussion <span class="text-red-500">*</span></label>
                @include('research.partials.formatter-toolbar', ['target' => 'results'])
                <textarea name="results" id="results" rows="7" placeholder="Present your findings, then explain what they mean and relate them to previous studies..."
                    class="fmt-textarea w-full px-4 py-3 border-2 border-gray-200 rounded-b-xl rounded-t-none focus:outline-none focus:border-orange-500 @error('results') border-red-400 @enderror">{{ old('results') }}</textarea>
                <div id="preview-results" class="fmt-preview hidden"></div>
                <p class="text-xs text-gray-400 mt-1"><i class="fas fa-info-circle mr-1"></i>Write the findings and their interpretation in one combined section.</p>
                @error('results')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Conclusion & Recommendations --}}
            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-lg font-bold text-gray-800 mb-1"><i class="fas fa-flag-checkered text-orange-500 mr-2"></i>Conclusion & Recommendations</h3>
                <p class="text-sm text-gray-500 mb-4">Summarize findings and provide actionable recommendations.</p>
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Conclusion <span class="text-red-500">*</span></label>
                @include('research.partials.formatter-toolbar', ['target' => 'conclusion'])
                <textarea name="conclusion" id="conclusion" rows="4" placeholder="Summary of key findings and their implications..."
                    class="fmt-textarea w-full px-4 py-3 border-2 border-gray-200 rounded-b-xl rounded-t-none focus:outline-none focus:border-orange-500 @error('conclusion') border-red-400 @enderror">{{ old('conclusion') }}</textarea>
                <div id="preview-conclusion" class="fmt-preview hidden"></div>
                @error('conclusion')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Recommendations <span class="text-red-500">*</span></label>
                @include('research.partials.formatter-toolbar', ['target' => 'recommendations'])
                <textarea name="recommendations" id="recommendations" rows="4" placeholder="Actionable recommendations based on the findings..."
                    class="fmt-textarea w-full px-4 py-3 border-2 border-gray-200 rounded-b-xl rounded-t-none focus:outline-none focus:border-orange-500 @error('recommendations') border-red-400 @enderror">{{ old('recommendations') }}</textarea>
                <div id="preview-recommendations" class="fmt-preview hidden"></div>
                @error('recommendations')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- References --}}
            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-lg font-bold text-gray-800 mb-1"><i class="fas fa-book text-orange-500 mr-2"></i>References</h3>
                <p class="text-sm text-gray-500 mb-4">List all references in APA 7th edition format. One reference per line.</p>
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">References <span class="text-red-500">*</span></label>
                @include('research.partials.formatter-toolbar', ['target' => 'references'])
                <textarea name="references" id="references" rows="8" placeholder="Author, A. A. (Year). Title of work. Publisher. https://doi.org/xxxxx&#10;Author, B. B., & Author, C. C. (Year). Title of article. Title of Periodical, volume(issue), page–page."
                    class="fmt-textarea w-full px-4 py-3 border-2 border-gray-200 rounded-b-xl rounded-t-none focus:outline-none focus:border-orange-500 font-mono text-sm leading-relaxed @error('references') border-red-400 @enderror">{{ old('references') }}</textarea>
                <div id="preview-references" class="fmt-preview hidden"></div>
                <p class="text-xs text-gray-400 mt-1"><i class="fas fa-info-circle mr-1"></i>Follow APA 7th edition format. One reference per line.</p>
                @error('references')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Classification --}}
            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4"><i class="fas fa-info-circle text-orange-500 mr-2"></i>Classification</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">College <span class="text-red-500">*</span></label>
                    <select name="college_id" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500 @error('college_id') border-red-400 @enderror">
                        <option value="">Select College</option>
                        @foreach($colleges as $college)
                        <option value="{{ $college->id }}" {{ old('college_id', session('user_college_id')) == $college->id ? 'selected' : '' }}>{{ $college->code }} - {{ $college->name }}</option>
                        @endforeach
                    </select>
                    @error('college_id')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Category <span class="text-red-500">*</span></label>
                    <select name="category_id" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500 @error('category_id') border-red-400 @enderror">
                        <option value="">Select Category</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">CSU Thrusts <span class="text-red-500">*</span></label>
                    <input type="hidden" name="thrust" id="thrustInput" value="{{ old('thrust') }}">
                    <div class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl bg-gray-50 text-gray-700 min-h-[52px] @error('thrust') border-red-400 @enderror">
                        <div>
                            <p id="thrustValue" class="font-semibold text-gray-800">{{ old('thrust') ?: 'AI will suggest the closest thrust automatically...' }}</p>
                            <p id="thrustMeta" class="text-xs text-gray-500 mt-1">Not sure? Fill in the title, abstract, and keywords, and AI will suggest one or more CSU thrusts for you.</p>
                        </div>
                        <div id="thrustStatus" class="text-xs font-semibold text-orange-600 whitespace-nowrap mt-2">AI-assisted</div>
                        <div id="thrustTags" class="flex flex-wrap gap-2 mt-3"></div>
                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach($thrustOptions as $thrustOption)
                            <label class="flex items-start gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 hover:border-orange-300">
                                <input type="checkbox" name="thrusts[]" value="{{ $thrustOption }}" class="mt-1 h-4 w-4 rounded border-gray-300 text-orange-600 focus:ring-orange-500" {{ in_array($thrustOption, old('thrusts', []), true) ? 'checked' : '' }}>
                                <span>{{ $thrustOption }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @error('thrust')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    @error('thrusts')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Publication Year <span class="text-red-500">*</span></label>
                    <select name="publication_year" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500 @error('publication_year') border-red-400 @enderror">
                        <option value="">Select Year</option>
                        @for($y = date('Y') + 1; $y >= 2000; $y--)
                        <option value="{{ $y }}" {{ old('publication_year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                    @error('publication_year')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            </div>

            <div class="flex flex-col sm:flex-row justify-end gap-3 pt-2">
                <a href="{{ route('research.index') }}" class="bg-gray-100 text-gray-700 px-6 py-3 rounded-xl font-semibold hover:bg-gray-200 transition">Cancel</a>
                <button type="button" id="saveDraftBtn" class="bg-blue-100 text-blue-700 px-6 py-3 rounded-xl font-semibold hover:bg-blue-200 transition flex items-center justify-center gap-2">
                    <i class="fas fa-save"></i> <span>Save Draft</span>
                </button>
                <button type="submit" class="bg-gradient-to-r from-orange-600 to-orange-700 text-white px-8 py-3 rounded-xl font-bold hover:from-orange-700 hover:to-orange-800 transition shadow">
                    <i class="fas fa-paper-plane mr-1"></i> {{ session('user_role') === 'student' ? 'Submit for Approval' : 'Archive Paper' }}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// ── Formatter Functions ──
function wrapSelection(fieldId, before, after) {
    const ta = document.getElementById(fieldId);
    if (!ta) return;
    ta.focus();
    const start = ta.selectionStart;
    const end = ta.selectionEnd;
    const selected = ta.value.substring(start, end);
    const replacement = before + (selected || 'text') + after;
    ta.value = ta.value.substring(0, start) + replacement + ta.value.substring(end);
    // Select the inner text for easy replacement
    const innerStart = start + before.length;
    const innerEnd = innerStart + (selected || 'text').length;
    ta.setSelectionRange(innerStart, innerEnd);
    ta.dispatchEvent(new Event('input'));
}

function indentSelection(fieldId) {
    const ta = document.getElementById(fieldId);
    if (!ta) return;

    ta.focus();
    const start = ta.selectionStart;
    const end = ta.selectionEnd;
    const blockStart = ta.value.lastIndexOf('\n', Math.max(0, start - 1)) + 1;
    const nextNewline = ta.value.indexOf('\n', end);
    const blockEnd = nextNewline === -1 ? ta.value.length : nextNewline;
    const block = ta.value.substring(blockStart, blockEnd);
    const indentedBlock = '\t' + block.replace(/\n/g, '\n\t');
    const lineCount = (block.match(/\n/g) || []).length + 1;

    ta.value = ta.value.substring(0, blockStart) + indentedBlock + ta.value.substring(blockEnd);
    ta.setSelectionRange(start + 1, end + lineCount);
    ta.dispatchEvent(new Event('input'));
}

function insertAtCursor(fieldId, text) {
    const ta = document.getElementById(fieldId);
    if (!ta) return;
    ta.focus();
    const start = ta.selectionStart;
    const end = ta.selectionEnd;
    const before = ta.value.substring(0, start);
    const after = ta.value.substring(end);
    const needsNewline = before.length > 0 && !before.endsWith('\n') ? '\n\n' : '';
    ta.value = before + needsNewline + text + '\n' + after;
    const newPos = (before + needsNewline + text + '\n').length;
    ta.setSelectionRange(newPos, newPos);
    ta.dispatchEvent(new Event('input'));
}

function insertTable(fieldId) {
    const template = '| Column 1 | Column 2 | Column 3 |\n| Data | Data | Data |\n| Data | Data | Data |';
    insertAtCursor(fieldId, template);
}

function insertFigure(fieldId) {
    const template = '[figure: filename.png | Figure X. Description here]';
    insertAtCursor(fieldId, template);
}

function getTableDesign() {
    const allowed = ['classic', 'striped', 'minimal'];
    const hiddenInput = document.getElementById('tableDesignInput');
    if (hiddenInput && allowed.includes(hiddenInput.value)) {
        return hiddenInput.value;
    }
    const saved = localStorage.getItem('researchTableDesign');
    return allowed.includes(saved) ? saved : 'classic';
}

function setTableDesignInput(styleName) {
    const hiddenInput = document.getElementById('tableDesignInput');
    if (hiddenInput) hiddenInput.value = styleName;
}

function syncTableDesignSelectors(styleName) {
    document.querySelectorAll('select[id^="table-design-"]').forEach(sel => {
        if (sel.value !== styleName) sel.value = styleName;
    });
}

function refreshVisiblePreviews() {
    document.querySelectorAll('.fmt-preview').forEach(preview => {
        if (preview.classList.contains('hidden')) return;
        const fieldId = preview.id.replace('preview-', '');
        const textarea = document.getElementById(fieldId);
        if (textarea) renderPreview(textarea.value, preview);
    });
}

function setTableDesign(styleName) {
    localStorage.setItem('researchTableDesign', styleName);
    setTableDesignInput(styleName);
    syncTableDesignSelectors(styleName);
    refreshVisiblePreviews();
}

function togglePreview(fieldId) {
    const ta = document.getElementById(fieldId);
    const preview = document.getElementById('preview-' + fieldId);
    const btn = document.querySelector(`.preview-toggle[data-target="${fieldId}"]`);
    if (!ta || !preview) return;

    if (preview.classList.contains('hidden')) {
        // Show preview
        preview.classList.remove('hidden');
        ta.classList.add('hidden');
        btn.classList.add('active');
        btn.querySelector('span').textContent = 'Edit';
        btn.querySelector('i').classList.replace('fa-eye', 'fa-edit');
        renderPreview(ta.value, preview);
    } else {
        // Show editor
        preview.classList.add('hidden');
        ta.classList.remove('hidden');
        btn.classList.remove('active');
        btn.querySelector('span').textContent = 'Preview';
        btn.querySelector('i').classList.replace('fa-edit', 'fa-eye');
    }
}

function renderPreview(text, container) {
    if (!text.trim()) {
        container.innerHTML = '<p class="text-gray-400 italic text-sm">Nothing to preview.</p>';
        return;
    }
    const lines = text.split('\n');
    let html = '';
    let tableRows = [];
    let inTable = false;

    for (const line of lines) {
        const indentLevel = getIndentLevel(line);
        const trimmed = stripLeadingIndent(line).trim();

        // Figure syntax
        const figMatch = trimmed.match(/\[figure:\s*(.+?)\s*\|\s*(.+?)\s*\]/);
        if (figMatch) {
            if (inTable && tableRows.length) { html += buildTableHtml(tableRows); tableRows = []; inTable = false; }
            const parts = trimmed.split(/\[figure:\s*.+?\s*\|\s*.+?\s*\]/);
            if (parts[0] && parts[0].trim()) html += buildParagraphHtml(parts[0].trim(), indentLevel);
            html += `<div class="figure-container"><img src="/storage/research_images/${escHtml(figMatch[1])}" class="figure-image" onerror="this.outerHTML='<p style=\\'text-align:center;color:#9ca3af;font-style:italic\\'>[Image: ${escHtml(figMatch[1])}]</p>'"><p class="figure-caption">${escHtml(figMatch[2])}</p></div>`;
            if (parts[1] && parts[1].trim()) html += buildParagraphHtml(parts[1].trim(), indentLevel);
            continue;
        }

        // Table row
        if (/^\|.*\|$/.test(trimmed)) {
            const cells = trimmed.split('|').slice(1, -1).map(c => c.trim());
            if (cells.length && /^[-:\s]+$/.test(cells[0])) continue;
            tableRows.push(cells);
            inTable = true;
        } else {
            if (inTable && tableRows.length) { html += buildTableHtml(tableRows); tableRows = []; inTable = false; }
            if (trimmed === '') continue;
            html += buildParagraphHtml(trimmed, indentLevel);
        }
    }
    if (tableRows.length) html += buildTableHtml(tableRows);
    container.innerHTML = html;
}

function formatInline(text) {
    // Bold: **text**
    text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    // Italic: *text*
    text = text.replace(/\*(.+?)\*/g, '<em>$1</em>');
    // Underline: __text__
    text = text.replace(/__(.+?)__/g, '<u>$1</u>');
    return text;
}

function buildParagraphHtml(text, indentLevel = 0) {
    const style = indentLevel > 0 ? ` style="margin-left:${indentLevel * 2}em;"` : '';
    return `<p class="section-content"${style}>${formatInline(escHtml(text))}</p>`;
}

function getIndentLevel(line) {
    let level = 0;
    let offset = 0;

    while (offset < line.length) {
        if (line[offset] === '\t') {
            level += 1;
            offset += 1;
            continue;
        }

        if (line.slice(offset, offset + 4) === '    ') {
            level += 1;
            offset += 4;
            continue;
        }

        break;
    }

    return level;
}

function stripLeadingIndent(line) {
    let offset = 0;

    while (offset < line.length) {
        if (line[offset] === '\t') {
            offset += 1;
            continue;
        }

        if (line.slice(offset, offset + 4) === '    ') {
            offset += 4;
            continue;
        }

        break;
    }

    return line.slice(offset);
}

function buildTableHtml(rows) {
    if (!rows.length) return '';
    const tableDesign = getTableDesign();
    const header = rows.shift();
    let h = `<table class="content-table table-style-${tableDesign}"><thead><tr>` + header.map(c => `<th>${formatInline(escHtml(c))}</th>`).join('') + '</tr></thead>';
    if (rows.length) {
        h += '<tbody>' + rows.map(r => '<tr>' + r.map(c => `<td>${formatInline(escHtml(c))}</td>`).join('') + '</tr>').join('') + '</tbody>';
    }
    return h + '</table>';
}

function escHtml(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

// ── Image Upload ──
document.getElementById('figureUpload').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;

    const status = document.getElementById('uploadStatus');
    const container = document.getElementById('uploadedFigures');
    status.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Uploading...';

    const formData = new FormData();
    formData.append('image', file);
    formData.append('_token', '{{ csrf_token() }}');

    fetch('{{ route("research.upload-image") }}', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.error) { status.textContent = 'Error: ' + data.error; return; }
            status.innerHTML = '<i class="fas fa-check text-green-500 mr-1"></i> Uploaded!';
            container.classList.remove('hidden');
            const item = document.createElement('div');
            item.className = 'flex items-center gap-2 bg-white border border-gray-200 rounded-lg px-3 py-2';
            item.innerHTML = `
                <img src="${data.url}" class="w-10 h-10 object-cover rounded border">
                <input type="text" value="${data.syntax}" readonly class="flex-1 text-xs font-mono bg-gray-50 px-2 py-1 rounded border border-gray-200 text-gray-600" onclick="this.select()">
                <button type="button" class="copy-syntax-btn text-orange-500 hover:text-orange-700 text-sm px-2" title="Copy syntax"><i class="fas fa-copy"></i></button>
                <button type="button" class="delete-fig-btn text-red-400 hover:text-red-600 text-sm px-2" data-filename="${data.filename}" title="Delete figure"><i class="fas fa-trash-alt"></i></button>
            `;
            item.querySelector('.copy-syntax-btn').addEventListener('click', function() {
                const input = item.querySelector('input');
                navigator.clipboard.writeText(input.value);
                this.innerHTML = '<i class="fas fa-check text-green-500"></i>';
                setTimeout(() => { this.innerHTML = '<i class="fas fa-copy"></i>'; }, 2000);
            });
            item.querySelector('.delete-fig-btn').addEventListener('click', function() {
                if (!confirm('Delete this figure? This cannot be undone.')) return;
                const btn = this;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                fetch('{{ route("research.delete-image") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ filename: btn.dataset.filename })
                })
                .then(r => r.json())
                .then(d => {
                    if (d.success) { item.remove(); if (!container.querySelector('.flex')) container.classList.add('hidden'); }
                    else { btn.innerHTML = '<i class="fas fa-trash-alt"></i>'; alert(d.error || 'Delete failed.'); }
                })
                .catch(() => { btn.innerHTML = '<i class="fas fa-trash-alt"></i>'; alert('Delete failed.'); });
            });
            container.appendChild(item);
            e.target.value = '';
            setTimeout(() => { status.textContent = ''; }, 2000);
        })
        .catch(() => { status.textContent = 'Upload failed.'; });
});

// ── Draft Auto-Save ──
const form = document.getElementById('researchForm');
const saveDraftBtn = document.getElementById('saveDraftBtn');
const saveStatusDiv = document.getElementById('saveStatus');
const thrustInput = document.getElementById('thrustInput');
const thrustValue = document.getElementById('thrustValue');
const thrustMeta = document.getElementById('thrustMeta');
const thrustStatus = document.getElementById('thrustStatus');
const thrustTags = document.getElementById('thrustTags');
let autoSaveInterval;
let hasChanges = false;
let thrustRequestTimer;
let thrustRequestAbort = null;

// Mark form as changed
form.addEventListener('change', () => { hasChanges = true; });
form.addEventListener('input', () => { hasChanges = true; });

function getThrustPayload() {
    return {
        title: document.querySelector('input[name="title"]').value || '',
        keywords: document.querySelector('input[name="keywords"]').value || '',
        abstract: document.getElementById('abstract').value || '',
        introduction: document.getElementById('introduction').value || '',
        methodology: document.getElementById('methodology').value || '',
        results: document.getElementById('results').value || '',
        conclusion: document.getElementById('conclusion').value || '',
        recommendations: document.getElementById('recommendations').value || '',
    };
}

function selectedThrustsFromForm() {
    return Array.from(document.querySelectorAll('input[name="thrusts[]"]:checked')).map(input => input.value);
}

function renderThrustTags(thrusts) {
    if (!thrustTags) return;

    thrustTags.innerHTML = '';

    if (!thrusts.length) {
        thrustTags.innerHTML = '<span class="text-xs text-gray-400">No thrust selected yet.</span>';
        return;
    }

    thrusts.forEach(thrust => {
        const chip = document.createElement('span');
        chip.className = 'inline-flex items-center rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-800';
        chip.textContent = thrust;
        thrustTags.appendChild(chip);
    });
}

function setThrustSuggestion(thrust, reason, source, thrusts = []) {
    if (!thrustInput || !thrustValue || !thrustMeta || !thrustStatus) return;

    thrustInput.value = thrust || '';
    thrustValue.textContent = thrusts.length ? `${thrust || 'Detected thrust'} (${thrusts.length})` : (thrust || 'Detecting thrust automatically...');
    thrustMeta.textContent = reason || 'Based on the title, abstract, and keywords.';
    thrustStatus.textContent = source === 'ollama' ? 'AI' : (source === 'keyword' ? 'Auto' : 'Pending');
    renderThrustTags(thrusts.length ? thrusts : (thrust ? [thrust] : []));

    document.querySelectorAll('input[name="thrusts[]"]').forEach(input => {
        input.checked = thrusts.includes(input.value);
    });
}

function requestThrustSuggestion() {
    const payload = getThrustPayload();

    if (thrustRequestAbort) {
        thrustRequestAbort.abort();
    }

    thrustRequestAbort = new AbortController();
    const signal = thrustRequestAbort.signal;

    if (thrustStatus) {
        thrustStatus.textContent = 'Checking...';
    }

    fetch('{{ route("research.thrust-suggestion") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
        },
        body: JSON.stringify(payload),
        signal,
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success || !data.suggestion) return;
        setThrustSuggestion(data.suggestion.thrust, data.suggestion.reason, data.suggestion.source, data.suggestion.thrusts || []);
    })
    .catch(err => {
        if (err.name === 'AbortError') return;
        if (thrustStatus) {
            thrustStatus.textContent = 'Auto';
        }
    });
}

function scheduleThrustSuggestion() {
    clearTimeout(thrustRequestTimer);
    thrustRequestTimer = setTimeout(requestThrustSuggestion, 450);
}

['title', 'keywords', 'abstract', 'introduction', 'methodology', 'results', 'conclusion', 'recommendations'].forEach(fieldId => {
    const field = document.getElementById(fieldId);
    if (!field) return;
    field.addEventListener('input', scheduleThrustSuggestion);
    field.addEventListener('change', scheduleThrustSuggestion);
});

document.querySelectorAll('input[name="thrusts[]"]').forEach(input => {
    input.addEventListener('change', () => {
        const thrusts = selectedThrustsFromForm();
        renderThrustTags(thrusts);
        if (thrustInput) thrustInput.value = thrusts[0] || '';
        if (thrustValue) thrustValue.textContent = thrusts.length ? `${thrusts[0]} (${thrusts.length})` : 'Detecting thrust automatically...';
    });
});

// Manual save draft button
saveDraftBtn.addEventListener('click', (e) => {
    e.preventDefault();
    saveDraft();
});

// Auto-save every 30 seconds
function startAutoSave() {
    autoSaveInterval = setInterval(() => {
        if (hasChanges) {
            saveDraft(true); // true = auto-save (silent)
        }
    }, 30000);
}

function saveDraft(isSilent = false) {
    const data = new FormData(form);
    const obj = {};

    for (const [key, value] of data.entries()) {
        if (key === 'thrusts[]') {
            if (!Array.isArray(obj.thrusts)) {
                obj.thrusts = [];
            }

            obj.thrusts.push(value);
            continue;
        }

        if (Object.prototype.hasOwnProperty.call(obj, key)) {
            if (!Array.isArray(obj[key])) {
                obj[key] = [obj[key]];
            }

            obj[key].push(value);
            continue;
        }

        obj[key] = value;
    }

    if (thrustInput && !thrustInput.value) {
        requestThrustSuggestion();
    }

    showStatus('saving');
    const csrfToken = document.querySelector('input[name="_token"]').value;

    fetch('{{ route("research.save-draft") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify(obj),
    })
    .then(r => r.json())
    .then(data => {
        hasChanges = false;
        if (data.success) {
            if (!isSilent) showStatus('saved', 'Draft saved successfully');
            else showStatus('saved', 'Auto-saved');
        } else {
            showStatus('error', data.message || 'Failed to save draft');
        }
    })
    .catch(err => {
        showStatus('error', 'Save failed. Check your connection.');
    });
}

function showStatus(type, message = '') {
    saveStatusDiv.className = 'save-status';
    if (type === 'saving') {
        saveStatusDiv.classList.add('saving');
        saveStatusDiv.innerHTML = '<div class="spinner"></div> Saving draft...';
    } else if (type === 'saved') {
        saveStatusDiv.classList.add('saved');
        saveStatusDiv.innerHTML = `<i class="fas fa-check"></i> ${message}`;
        setTimeout(() => {
            saveStatusDiv.innerHTML = '';
            saveStatusDiv.className = 'save-status';
        }, 3000);
    } else if (type === 'error') {
        saveStatusDiv.classList.add('error');
        saveStatusDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
        setTimeout(() => {
            saveStatusDiv.innerHTML = '';
            saveStatusDiv.className = 'save-status';
        }, 5000);
    }
}

// Load draft data from modal
function loadDraftData() {
    fetch('{{ route("research.load-draft") }}')
        .then(r => r.json())
        .then(data => {
            if (data.success && data.draft) {
                Object.keys(data.draft).forEach(key => {
                    if (key === 'thrusts' && Array.isArray(data.draft[key])) {
                        document.querySelectorAll('input[name="thrusts[]"]').forEach(input => {
                            input.checked = data.draft[key].includes(input.value);
                        });
                        renderThrustTags(data.draft[key]);
                        return;
                    }

                    const field = form.querySelector(`[name="${key}"]`);
                    if (field) {
                        field.value = data.draft[key] || '';
                        field.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
                if (data.draft.table_design) {
                    setTableDesign(data.draft.table_design);
                }
                if (data.draft.thrusts && Array.isArray(data.draft.thrusts) && data.draft.thrusts.length) {
                    setThrustSuggestion(data.draft.thrusts[0], 'Draft thrusts restored.', 'saved', data.draft.thrusts);
                }
                document.getElementById('draftModal').remove();
                showStatus('saved', 'Draft loaded');
            }
        })
        .catch(() => showStatus('error', 'Failed to load draft'));
}

function startFresh() {
    if (confirm('Delete the existing draft and start fresh?')) {
        fetch('{{ route("research.delete-draft") }}', {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('draftModal').remove();
                form.reset();
                hasChanges = false;
            }
        });
    }
}

// Start auto-save on load
startAutoSave();
setTableDesign(getTableDesign());
const initialThrusts = selectedThrustsFromForm();
renderThrustTags(initialThrusts);
if (initialThrusts.length > 0) {
    setThrustSuggestion(initialThrusts[0], 'Current thrust selection loaded.', 'saved', initialThrusts);
} else {
    requestThrustSuggestion();
}

// Alert user if they try to leave with unsaved changes
window.addEventListener('beforeunload', (e) => {
    if (hasChanges) {
        e.preventDefault();
        e.returnValue = 'You have unsaved changes. Your draft will be auto-saved.';
    }
});
</script>
@endsection
