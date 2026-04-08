@extends('layouts.app')
@section('title', 'Submit Research')
@section('page-title', 'Submit Research')
@section('page-subtitle', 'Add your research to the archive')
@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-orange-600 to-orange-800 p-6 text-white">
            <h2 class="text-xl font-bold"><i class="fas fa-file-upload mr-2"></i>Submit New Research</h2>
            <p class="text-orange-100 text-sm mt-1">Fill in all required fields to submit your research paper.</p>
        </div>
        <form action="{{ route('research.store') }}" method="POST" enctype="multipart/form-data" class="p-8 space-y-6">
            @csrf
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Research Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" value="{{ old('title') }}" placeholder="Enter the full title of your research..."
                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500 @error('title') border-red-400 @enderror">
                @error('title')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Abstract <span class="text-red-500">*</span></label>
                <textarea name="abstract" rows="5" placeholder="Write a comprehensive summary of your research..."
                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500 @error('abstract') border-red-400 @enderror">{{ old('abstract') }}</textarea>
                @error('abstract')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

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

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Adviser</label>
                <select name="adviser_id" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500">
                    <option value="">Select Adviser (Optional)</option>
                    @foreach($advisers as $adviser)
                    <option value="{{ $adviser->id }}" {{ old('adviser_id') == $adviser->id ? 'selected' : '' }}>{{ $adviser->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Research Document</label>
                <div class="border-2 border-dashed border-orange-300 rounded-xl p-6 text-center bg-orange-50">
                    <i class="fas fa-cloud-upload-alt text-orange-400 text-3xl mb-2"></i>
                    <p class="text-gray-600 text-sm mb-2">Upload PDF, DOC, or DOCX (Max: 20MB)</p>
                    <input type="file" name="document" accept=".pdf,.doc,.docx" class="hidden" id="docUpload">
                    <label for="docUpload" class="bg-orange-600 text-white px-5 py-2 rounded-lg cursor-pointer hover:bg-orange-700 transition text-sm font-semibold">
                        Choose File
                    </label>
                    <p id="fileName" class="text-xs text-gray-500 mt-2">No file selected</p>
                </div>
                @error('document')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('research.index') }}" class="bg-gray-100 text-gray-700 px-6 py-3 rounded-xl font-semibold hover:bg-gray-200 transition">Cancel</a>
                <button type="submit" class="bg-gradient-to-r from-orange-600 to-orange-700 text-white px-8 py-3 rounded-xl font-bold hover:from-orange-700 hover:to-orange-800 transition shadow">
                    <i class="fas fa-paper-plane mr-1"></i> Submit Research
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
@section('scripts')
<script>
    document.getElementById('docUpload').addEventListener('change', function() {
        document.getElementById('fileName').textContent = this.files[0] ? this.files[0].name : 'No file selected';
    });
</script>
@endsection
