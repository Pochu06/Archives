@extends('layouts.app')
@section('title', 'Edit Thrust')
@section('page-title', 'Edit Thrust')
@section('page-subtitle', 'Update thrust information')
@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 p-6 text-white">
            <h2 class="text-xl font-bold"><i class="fas fa-bullseye mr-2"></i>Edit Thrust: {{ $thrust->name }}</h2>
        </div>
        <form action="{{ route('thrusts.update', $thrust->id) }}" method="POST" class="p-8 space-y-5">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Thrust Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $thrust->name) }}"
                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500 @error('name') border-red-400 @enderror">
                @error('name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Description</label>
                <textarea name="description" rows="4" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500 @error('description') border-red-400 @enderror">{{ old('description', $thrust->description) }}</textarea>
                @error('description')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Keywords</label>
                <textarea name="keywords" rows="4" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500 @error('keywords') border-red-400 @enderror">{{ old('keywords', $thrust->keywords) }}</textarea>
                @error('keywords')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <label class="flex items-center gap-3 text-sm text-gray-700">
                <input type="checkbox" name="active" value="1" class="h-4 w-4 text-orange-600 border-gray-300 rounded" {{ old('active', $thrust->active) ? 'checked' : '' }}>
                <span>Active</span>
            </label>
            <div class="flex justify-end gap-3">
                <a href="{{ route('thrusts.index') }}" class="bg-gray-100 text-gray-700 px-6 py-3 rounded-xl font-semibold hover:bg-gray-200">Cancel</a>
                <button type="submit" class="bg-gradient-to-r from-orange-600 to-orange-700 text-white px-8 py-3 rounded-xl font-bold hover:from-orange-700 hover:to-orange-800 transition shadow">
                    <i class="fas fa-save mr-1"></i> Update Thrust
                </button>
            </div>
        </form>
    </div>
</div>
@endsection