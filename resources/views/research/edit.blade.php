@extends('layouts.app')
@section('title', 'Edit Research')
@section('page-title', 'Edit Research')
@section('page-subtitle', 'Update your research submission')
@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 p-6 text-white">
            <h2 class="text-xl font-bold"><i class="fas fa-edit mr-2"></i>Edit Research Submission</h2>
            <p class="text-blue-100 text-sm mt-1">Update the details of your research paper.</p>
        </div>
        <form action="{{ route('research.update', $research->id) }}" method="POST" enctype="multipart/form-data" class="p-8 space-y-6">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Research Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" value="{{ old('title', $research->title) }}"
                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500">
                @error('title')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Abstract <span class="text-red-500">*</span></label>
                <textarea name="abstract" rows="5" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500">{{ old('abstract', $research->abstract) }}</textarea>
                @error('abstract')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Authors <span class="text-red-500">*</span></label>
                    <input type="text" name="authors" value="{{ old('authors', $research->authors) }}"
                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Keywords <span class="text-red-500">*</span></label>
                    <input type="text" name="keywords" value="{{ old('keywords', $research->keywords) }}"
                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">College</label>
                    <select name="college_id" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500">
                        @foreach($colleges as $college)
                        <option value="{{ $college->id }}" {{ old('college_id', $research->college_id) == $college->id ? 'selected' : '' }}>{{ $college->code }}</option>
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
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Adviser</label>
                <select name="adviser_id" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500">
                    <option value="">No Adviser</option>
                    @foreach($advisers as $adviser)
                    <option value="{{ $adviser->id }}" {{ old('adviser_id', $research->adviser_id) == $adviser->id ? 'selected' : '' }}>{{ $adviser->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Replace Document</label>
                @if($research->file_name)
                <div class="bg-orange-50 border border-orange-200 rounded-xl p-3 mb-3 flex items-center">
                    <i class="fas fa-file text-orange-500 mr-2"></i>
                    <span class="text-sm text-gray-700">Current: {{ $research->file_name }}</span>
                </div>
                @endif
                <input type="file" name="document" accept=".pdf,.doc,.docx" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm">
            </div>
            <div class="flex justify-end gap-3">
                <a href="{{ route('research.show', $research->id) }}" class="bg-gray-100 text-gray-700 px-6 py-3 rounded-xl font-semibold hover:bg-gray-200 transition">Cancel</a>
                <button type="submit" class="bg-gradient-to-r from-orange-600 to-orange-700 text-white px-8 py-3 rounded-xl font-bold hover:from-orange-700 hover:to-orange-800 transition shadow">
                    <i class="fas fa-save mr-1"></i> Update Research
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
