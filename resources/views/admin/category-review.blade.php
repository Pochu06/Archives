@extends('layouts.app')
@section('title', 'Research Classification Review')
@section('page-title', 'Research Classification Review')
@section('page-subtitle', 'Review and correct existing academic level and category metadata')
@section('content')
<div class="space-y-5">
    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
        <p class="font-semibold">Existing records are not changed automatically.</p>
        <p class="mt-1">Review each paper and choose a valid academic level and matching research category. Every correction is recorded in the paper’s audit history.</p>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="border-b border-gray-100 p-5">
            <h2 class="font-bold text-gray-900">Records needing review <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800">{{ $research->total() }}</span></h2>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($research as $paper)
            <div class="grid gap-4 p-5 lg:grid-cols-[minmax(0,1fr)_minmax(20rem,1fr)]">
                <div>
                    <a href="{{ route('research.show', \App\Support\ResearchToken::encode($paper->id)) }}" class="font-semibold text-orange-700 hover:underline">{{ $paper->title }}</a>
                    <p class="mt-1 text-sm text-gray-600">{{ $paper->college?->code ?? 'College unavailable' }} · {{ $paper->user?->name ?? 'Submitter unavailable' }}</p>
                    <p class="mt-2 text-xs text-gray-500">
                        Current classification:
                        {{ $academicLevels[$paper->academic_level] ?? 'Academic level missing or invalid' }}
                        · {{ $paper->category?->name ?? 'Category missing' }}
                    </p>
                </div>
                <form method="POST" action="{{ route('submissions.category-classification.update', $paper->id) }}" class="grid gap-3 sm:grid-cols-3">
                    @csrf
                    @method('PUT')
                    <label class="text-xs font-semibold text-gray-600">
                        Academic level
                        <select name="academic_level" data-classification-level required class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm">
                            <option value="">Select level</option>
                            @foreach($academicLevels as $level => $label)
                            <option value="{{ $level }}" {{ $paper->academic_level === $level ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-xs font-semibold text-gray-600">
                        Research category
                        <select name="category_id" data-classification-category required class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm">
                            <option value="">Select category</option>
                            @foreach($categories as $category)
                            <option value="{{ $category->id }}" data-category-name="{{ strtolower($category->name) }}" {{ (int) $paper->category_id === (int) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="flex items-end">
                        <button type="submit" class="w-full rounded-xl bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-orange-700">Save correction</button>
                    </div>
                    @error('academic_level')<p class="text-sm text-red-600 sm:col-span-3">{{ $message }}</p>@enderror
                    @error('category_id')<p class="text-sm text-red-600 sm:col-span-3">{{ $message }}</p>@enderror
                </form>
            </div>
            @empty
            <p class="p-8 text-center text-sm text-gray-500">All research records have a valid academic level and category.</p>
            @endforelse
        </div>
        <div class="border-t border-gray-100 p-4">{{ $research->links() }}</div>
    </div>
</div>
<script>
document.querySelectorAll('[data-classification-level]').forEach((level) => {
    const form = level.closest('form');
    const category = form.querySelector('[data-classification-category]');
    const allowed = { BS: ['thesis', 'capstone project'], GS: ['thesis', 'dissertation'] };
    const updateOptions = () => {
        const previous = category.value;
        const names = allowed[level.value] || [];
        Array.from(category.options).forEach((option) => {
            if (!option.value) return;
            option.hidden = !names.includes(option.dataset.categoryName);
            option.disabled = option.hidden;
        });
        if (!names.includes(category.selectedOptions[0]?.dataset.categoryName)) category.value = '';
        if (previous && !category.value) category.setCustomValidity('Choose a category valid for this academic level.');
        else category.setCustomValidity('');
    };
    level.addEventListener('change', updateOptions);
    category.addEventListener('change', () => category.setCustomValidity(''));
    updateOptions();
});
</script>
@endsection
