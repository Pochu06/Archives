{{-- Formatter Toolbar - include above any textarea that supports tables/figures --}}
{{-- Usage: @include('research.partials.formatter-toolbar', ['target' => 'fieldname']) --}}
<div class="flex flex-wrap items-center gap-1 bg-gray-50 border border-b-0 border-gray-200 rounded-t-xl px-3 py-1.5">
    <button type="button" onclick="wrapSelection('{{ $target }}', '**', '**')" class="fmt-btn" title="Bold (**text**)">
        <i class="fas fa-bold"></i>
    </button>
    <button type="button" onclick="wrapSelection('{{ $target }}', '*', '*')" class="fmt-btn" title="Italic (*text*)">
        <i class="fas fa-italic"></i>
    </button>
    <button type="button" onclick="wrapSelection('{{ $target }}', '__', '__')" class="fmt-btn" title="Underline (__text__)">
        <i class="fas fa-underline"></i>
    </button>
    <button type="button" onclick="indentSelection('{{ $target }}')" class="fmt-btn" title="Indent selected paragraph or lines">
        <i class="fas fa-indent"></i>
    </button>
    <span class="w-px h-4 bg-gray-300 mx-1"></span>
    <button type="button" onclick="insertTable('{{ $target }}')" class="fmt-btn" title="Insert Table">
        <i class="fas fa-table"></i>
    </button>
    <button type="button" onclick="insertFigure('{{ $target }}')" class="fmt-btn" title="Insert Figure Placeholder">
        <i class="fas fa-image"></i>
    </button>
    <div class="w-full sm:w-auto sm:ml-auto flex items-center justify-between sm:justify-end gap-1 mt-1 sm:mt-0">
        <div class="flex items-center gap-1 min-w-0">
            <label class="text-xs text-gray-500" for="table-design-{{ $target }}">Table</label>
            <select
                id="table-design-{{ $target }}"
                class="text-xs border border-gray-300 rounded-md px-2 py-1 bg-white text-gray-700 max-w-[110px]"
                onchange="setTableDesign(this.value)">
                <option value="classic">Classic</option>
                <option value="striped">Striped</option>
                <option value="minimal">Minimal</option>
            </select>
        </div>
        <button type="button" onclick="togglePreview('{{ $target }}')" class="fmt-btn preview-toggle" data-target="{{ $target }}" title="Toggle Preview">
            <i class="fas fa-eye"></i> <span class="text-xs ml-0.5 hidden sm:inline">Preview</span>
        </button>
    </div>
</div>
