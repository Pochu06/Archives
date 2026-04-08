@extends('layouts.app')
@section('title', 'Edit Category')
@section('page-title', 'Edit Category')
@section('page-subtitle', 'Update category information')
@section('content')
<div class="max-w-lg mx-auto">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 p-6 text-white">
            <h2 class="text-xl font-bold"><i class="fas fa-tag mr-2"></i>Edit Category: {{ $category->name }}</h2>
        </div>
        <form action="{{ route('categories.update', $category->id) }}" method="POST" class="p-8 space-y-5">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Category Name</label>
                <input type="text" name="name" value="{{ old('name', $category->name) }}"
                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500">
                @error('name')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Description</label>
                <textarea name="description" rows="3" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500">{{ old('description', $category->description) }}</textarea>
            </div>
            <div class="flex justify-end gap-3">
                <a href="{{ route('categories.index') }}" class="bg-gray-100 text-gray-700 px-6 py-3 rounded-xl font-semibold hover:bg-gray-200">Cancel</a>
                <button type="submit" class="bg-gradient-to-r from-orange-600 to-orange-700 text-white px-8 py-3 rounded-xl font-bold hover:from-orange-700 hover:to-orange-800 transition shadow">
                    <i class="fas fa-save mr-1"></i> Update Category
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
