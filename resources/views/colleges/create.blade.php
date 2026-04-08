@extends('layouts.app')
@section('title', 'Add College')
@section('page-title', 'Add College')
@section('page-subtitle', 'Register a new participating college')
@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-orange-600 to-orange-800 p-6 text-white">
            <h2 class="text-xl font-bold"><i class="fas fa-university mr-2"></i>Add New College</h2>
        </div>
        <form action="{{ route('colleges.store') }}" method="POST" class="p-8 space-y-5">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">College Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500 @error('name') border-red-400 @enderror">
                    @error('name')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Code <span class="text-red-500">*</span></label>
                    <input type="text" name="code" value="{{ old('code') }}" placeholder="e.g., CICS" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500 @error('code') border-red-400 @enderror">
                    @error('code')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Description</label>
                <textarea name="description" rows="3" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500">{{ old('description') }}</textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Dean / Head</label>
                    <input type="text" name="dean" value="{{ old('dean') }}" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Contact Email</label>
                    <input type="email" name="contact_email" value="{{ old('contact_email') }}" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500">
                </div>
            </div>
            <div class="flex justify-end gap-3">
                <a href="{{ route('colleges.index') }}" class="bg-gray-100 text-gray-700 px-6 py-3 rounded-xl font-semibold hover:bg-gray-200">Cancel</a>
                <button type="submit" class="bg-gradient-to-r from-orange-600 to-orange-700 text-white px-8 py-3 rounded-xl font-bold hover:from-orange-700 hover:to-orange-800 transition shadow">
                    <i class="fas fa-save mr-1"></i> Create College
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
