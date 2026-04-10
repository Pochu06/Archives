@extends('layouts.app')
@section('title', 'Edit Research Paper')
@section('styles')
<style>
    .fmt-btn { @apply px-2.5 py-1 text-xs bg-white border border-gray-200 rounded-lg text-gray-600 cursor-pointer transition-all duration-150; }
    .fmt-btn:hover { @apply bg-orange-50 border-orange-300 text-orange-600; }
    .fmt-btn.active { @apply bg-orange-100 border-orange-400 text-orange-700; }
    .fmt-textarea { border-top-left-radius: 0 !important; border-top-right-radius: 0 !important; }
    .fmt-preview { @apply border-2 border-gray-200 rounded-b-xl p-4 min-h-[120px] bg-white; border-top-left-radius: 0; border-top-right-radius: 0; }
    .fmt-preview .section-content { margin-bottom: 0.5rem; font-size: 0.95rem; line-height: 1.6; color: #374151; }
    .fmt-preview .content-table { width: 100%; border-collapse: collapse; margin: 1rem 0; font-size: 0.85rem; }
    .fmt-preview .content-table th, .fmt-preview .content-table td { border: 1px solid #d1d5db; padding: 8px 12px; text-align: left; }
    .fmt-preview .content-table th { background: #f9fafb; font-weight: 600; }
    .fmt-preview .figure-container { text-align: center; margin: 1rem 0; }
    .fmt-preview .figure-image { max-width: 80%; margin: 0 auto; border-radius: 8px; }
    .fmt-preview .figure-caption { font-size: 0.85rem; color: #6b7280; margin-top: 0.5rem; font-style: italic; }
</style>
@endsection
@section('page-title', 'Edit Research Paper')
@section('page-subtitle', 'Update the archived research paper')
@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 p-6 text-white">
            <h2 class="text-xl font-bold"><i class="fas fa-edit mr-2"></i>Edit Research Paper</h2>
            <p class="text-blue-100 text-sm mt-1">Update the IMRAD sections and metadata.</p>
        </div>
        <form action="{{ route('research.update', $research->id) }}" method="POST" class="p-8 space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Research Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" value="{{ old('title', $research->title) }}"
                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500">
                @error('title')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Authors <span class="text-red-500">*</span></label>
                    <input type="text" name="authors" value="{{ old('authors', $research->authors) }}"
                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500">
                    @error('authors')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Keywords <span class="text-red-500">*</span></label>
                    <input type="text" name="keywords" value="{{ old('keywords', $research->keywords) }}"
                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500">
                    @error('keywords')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Abstract <span class="text-red-500">*</span></label>
                @include('research.partials.formatter-toolbar', ['target' => 'abstract'])
                <textarea name="abstract" id="abstract" rows="4" class="fmt-textarea w-full px-4 py-3 border-2 border-gray-200 rounded-b-xl rounded-t-none focus:outline-none focus:border-orange-500">{{ old('abstract', $research->abstract) }}</textarea>
                <div id="preview-abstract" class="fmt-preview hidden"></div>
                @error('abstract')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-lg font-bold text-gray-800 mb-1"><i class="fas fa-layer-group text-orange-500 mr-2"></i>IMRAD Sections</h3>
                <p class="text-sm text-gray-500 mb-4">Update the content for each section.</p>
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
                <textarea name="introduction" id="introduction" rows="5" class="fmt-textarea w-full px-4 py-3 border-2 border-gray-200 rounded-b-xl rounded-t-none focus:outline-none focus:border-orange-500">{{ old('introduction', $research->introduction) }}</textarea>
                <div id="preview-introduction" class="fmt-preview hidden"></div>
                @error('introduction')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Methodology <span class="text-red-500">*</span></label>
                @include('research.partials.formatter-toolbar', ['target' => 'methodology'])
                <textarea name="methodology" id="methodology" rows="5" class="fmt-textarea w-full px-4 py-3 border-2 border-gray-200 rounded-b-xl rounded-t-none focus:outline-none focus:border-orange-500">{{ old('methodology', $research->methodology) }}</textarea>
                <div id="preview-methodology" class="fmt-preview hidden"></div>
                @error('methodology')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Results and Discussion <span class="text-red-500">*</span></label>
                @include('research.partials.formatter-toolbar', ['target' => 'results'])
                <textarea name="results" id="results" rows="7" class="fmt-textarea w-full px-4 py-3 border-2 border-gray-200 rounded-b-xl rounded-t-none focus:outline-none focus:border-orange-500">{{ old('results', trim($research->results.(filled($research->results) && filled($research->discussion) ? "\n\n" : '').$research->discussion)) }}</textarea>
                <div id="preview-results" class="fmt-preview hidden"></div>
                <p class="text-xs text-gray-400 mt-1"><i class="fas fa-info-circle mr-1"></i>Keep findings and interpretation together in one section.</p>
                @error('results')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-lg font-bold text-gray-800 mb-1"><i class="fas fa-flag-checkered text-orange-500 mr-2"></i>Conclusion & Recommendations</h3>
                <p class="text-sm text-gray-500 mb-4">Update the conclusion and recommendations.</p>
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Conclusion <span class="text-red-500">*</span></label>
                @include('research.partials.formatter-toolbar', ['target' => 'conclusion'])
                <textarea name="conclusion" id="conclusion" rows="4" class="fmt-textarea w-full px-4 py-3 border-2 border-gray-200 rounded-b-xl rounded-t-none focus:outline-none focus:border-orange-500">{{ old('conclusion', $research->conclusion) }}</textarea>
                <div id="preview-conclusion" class="fmt-preview hidden"></div>
                @error('conclusion')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Recommendations <span class="text-red-500">*</span></label>
                @include('research.partials.formatter-toolbar', ['target' => 'recommendations'])
                <textarea name="recommendations" id="recommendations" rows="4" class="fmt-textarea w-full px-4 py-3 border-2 border-gray-200 rounded-b-xl rounded-t-none focus:outline-none focus:border-orange-500">{{ old('recommendations', $research->recommendations) }}</textarea>
                <div id="preview-recommendations" class="fmt-preview hidden"></div>
                @error('recommendations')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-lg font-bold text-gray-800 mb-1"><i class="fas fa-book text-orange-500 mr-2"></i>References</h3>
                <p class="text-sm text-gray-500 mb-4">APA 7th edition format. One reference per line.</p>
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">References <span class="text-red-500">*</span></label>
                @include('research.partials.formatter-toolbar', ['target' => 'references'])
                <textarea name="references" id="references" rows="8" class="fmt-textarea w-full px-4 py-3 border-2 border-gray-200 rounded-b-xl rounded-t-none focus:outline-none focus:border-orange-500 font-mono text-sm leading-relaxed">{{ old('references', $research->references) }}</textarea>
                <div id="preview-references" class="fmt-preview hidden"></div>
                <p class="text-xs text-gray-400 mt-1"><i class="fas fa-info-circle mr-1"></i>Follow APA 7th edition format. One reference per line.</p>
                @error('references')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4"><i class="fas fa-info-circle text-orange-500 mr-2"></i>Classification</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">College</label>
                    <select name="college_id" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500">
                        @foreach($colleges as $college)
                        <option value="{{ $college->id }}" {{ old('college_id', $research->college_id) == $college->id ? 'selected' : '' }}>{{ $college->code }} - {{ $college->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Category</label>
                    <select name="category_id" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500">
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id', $research->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Publication Year</label>
                    <select name="publication_year" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500">
                        @for($y = date('Y') + 1; $y >= 2000; $y--)
                        <option value="{{ $y }}" {{ old('publication_year', $research->publication_year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('research.show', $research->id) }}" class="bg-gray-100 text-gray-700 px-6 py-3 rounded-xl font-semibold hover:bg-gray-200 transition">Cancel</a>
                <button type="submit" class="bg-gradient-to-r from-orange-600 to-orange-700 text-white px-8 py-3 rounded-xl font-bold hover:from-orange-700 hover:to-orange-800 transition shadow">
                    <i class="fas fa-save mr-1"></i> Update Paper
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
    const innerStart = start + before.length;
    const innerEnd = innerStart + (selected || 'text').length;
    ta.setSelectionRange(innerStart, innerEnd);
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

function togglePreview(fieldId) {
    const ta = document.getElementById(fieldId);
    const preview = document.getElementById('preview-' + fieldId);
    const btn = document.querySelector(`.preview-toggle[data-target="${fieldId}"]`);
    if (!ta || !preview) return;

    if (preview.classList.contains('hidden')) {
        preview.classList.remove('hidden');
        ta.classList.add('hidden');
        btn.classList.add('active');
        btn.querySelector('span').textContent = 'Edit';
        btn.querySelector('i').classList.replace('fa-eye', 'fa-edit');
        renderPreview(ta.value, preview);
    } else {
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
        const trimmed = line.trim();
        const figMatch = trimmed.match(/\[figure:\s*(.+?)\s*\|\s*(.+?)\s*\]/);
        if (figMatch) {
            if (inTable && tableRows.length) { html += buildTableHtml(tableRows); tableRows = []; inTable = false; }
            const parts = trimmed.split(/\[figure:\s*.+?\s*\|\s*.+?\s*\]/);
            if (parts[0] && parts[0].trim()) html += `<p class="section-content">${escHtml(parts[0].trim())}</p>`;
            html += `<div class="figure-container"><img src="/storage/research_images/${escHtml(figMatch[1])}" class="figure-image" onerror="this.outerHTML='<p style=\'text-align:center;color:#9ca3af;font-style:italic\'>[Image: ${escHtml(figMatch[1])}]</p>'"><p class="figure-caption">${escHtml(figMatch[2])}</p></div>`;
            if (parts[1] && parts[1].trim()) html += `<p class="section-content">${escHtml(parts[1].trim())}</p>`;
            continue;
        }
        if (/^\|.*\|$/.test(trimmed)) {
            const cells = trimmed.split('|').slice(1, -1).map(c => c.trim());
            if (cells.length && /^[-:\s]+$/.test(cells[0])) continue;
            tableRows.push(cells);
            inTable = true;
        } else {
            if (inTable && tableRows.length) { html += buildTableHtml(tableRows); tableRows = []; inTable = false; }
            if (trimmed === '') continue;
            html += `<p class="section-content">${formatInline(escHtml(trimmed))}</p>`;
        }
    }
    if (tableRows.length) html += buildTableHtml(tableRows);
    container.innerHTML = html;
}

function formatInline(text) {
    text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    text = text.replace(/\*(.+?)\*/g, '<em>$1</em>');
    text = text.replace(/__(.+?)__/g, '<u>$1</u>');
    return text;
}

function buildTableHtml(rows) {
    if (!rows.length) return '';
    const header = rows.shift();
    let h = '<table class="content-table"><thead><tr>' + header.map(c => `<th>${formatInline(escHtml(c))}</th>`).join('') + '</tr></thead>';
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
</script>
@endsection
