{{-- Formatter Toolbar - include above any textarea that supports tables/figures --}}
{{-- Usage: @include('research.partials.formatter-toolbar', ['target' => 'fieldname']) --}}
<div class="flex items-center gap-1 bg-gray-50 border border-b-0 border-gray-200 rounded-t-xl px-3 py-1.5">
    <button type="button" onclick="wrapSelection('{{ $target }}', '**', '**')" class="fmt-btn" title="Bold (**text**)">
        <i class="fas fa-bold"></i>
    </button>
    <button type="button" onclick="wrapSelection('{{ $target }}', '*', '*')" class="fmt-btn" title="Italic (*text*)">
        <i class="fas fa-italic"></i>
    </button>
    <button type="button" onclick="wrapSelection('{{ $target }}', '__', '__')" class="fmt-btn" title="Underline (__text__)">
        <i class="fas fa-underline"></i>
    </button>
    <span class="w-px h-4 bg-gray-300 mx-1"></span>
    <button type="button" onclick="insertTable('{{ $target }}')" class="fmt-btn" title="Insert Table">
        <i class="fas fa-table"></i>
    </button>
    <button type="button" onclick="insertFigure('{{ $target }}')" class="fmt-btn" title="Insert Figure Placeholder">
        <i class="fas fa-image"></i>
    </button>
    <div class="flex-1"></div>
    <button type="button" onclick="togglePreview('{{ $target }}')" class="fmt-btn preview-toggle" data-target="{{ $target }}" title="Toggle Preview">
        <i class="fas fa-eye"></i> <span class="text-xs ml-0.5">Preview</span>
    </button>
</div>
