@extends('layouts.app')
@section('title', 'Edit College')
@section('page-title', 'Edit College')
@section('page-subtitle', 'Update college information')
@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 p-6 text-white">
            <h2 class="text-xl font-bold"><i class="fas fa-university mr-2"></i>Edit College: {{ $college->code }}</h2>
        </div>
        <form action="{{ route('colleges.update', $college->id) }}" method="POST" class="p-8 space-y-5">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">College Name</label>
                    <input type="text" name="name" value="{{ old('name', $college->name) }}" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500">
                    @error('name')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Code</label>
                    <input type="text" name="code" value="{{ old('code', $college->code) }}" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500">
                    @error('code')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Description</label>
                <textarea name="description" rows="3" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500">{{ old('description', $college->description) }}</textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Dean / Head</label>
                    <input type="text" name="dean" value="{{ old('dean', $college->dean) }}" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Contact Email</label>
                    <input type="email" name="contact_email" value="{{ old('contact_email', $college->contact_email) }}" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500">
                </div>
            </div>
            <div class="flex items-center">
                <input type="checkbox" name="active" id="active" {{ old('active', $college->active) ? 'checked' : '' }} class="w-4 h-4 text-orange-600 rounded">
                <label for="active" class="ml-2 text-gray-700 font-semibold">College is Active</label>
            </div>
            <div class="flex justify-end gap-3">
                <a href="{{ route('colleges.index') }}" class="bg-gray-100 text-gray-700 px-6 py-3 rounded-xl font-semibold hover:bg-gray-200">Cancel</a>
                <button type="submit" class="bg-gradient-to-r from-orange-600 to-orange-700 text-white px-8 py-3 rounded-xl font-bold hover:from-orange-700 hover:to-orange-800 transition shadow">
                    <i class="fas fa-save mr-1"></i> Update College
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
