@extends('layouts.app')
@section('title', 'Colleges')
@section('page-title', 'College Management')
@section('page-subtitle', 'Manage participating colleges')
@section('content')
<div class="space-y-5">
    <div class="flex justify-end">
        <a href="{{ route('colleges.create') }}" class="bg-gradient-to-r from-orange-600 to-orange-700 text-white px-5 py-2.5 rounded-xl font-semibold hover:from-orange-700 hover:to-orange-800 transition shadow">
            <i class="fas fa-plus mr-1"></i> Add College
        </a>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($colleges as $college)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md hover:border-orange-200 transition">
            <div class="bg-gradient-to-br from-orange-500 to-orange-700 p-5 rounded-t-2xl text-white">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-3xl font-extrabold">{{ $college->code }}</h3>
                        <p class="text-orange-100 text-xs mt-1 line-clamp-2">{{ $college->name }}</p>
                    </div>
                    <span class="text-xs px-2 py-1 rounded-full {{ $college->active ? 'bg-green-400/30 text-green-100' : 'bg-red-400/30 text-red-100' }} font-semibold">
                        {{ $college->active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>
            <div class="p-5">
                <p class="text-gray-600 text-sm mb-3">{{ $college->description ?? 'No description provided.' }}</p>
                <div class="grid grid-cols-2 gap-3 text-center mb-4">
                    <div class="bg-orange-50 rounded-xl p-3">
                        <p class="text-2xl font-bold text-orange-700">{{ $college->research_count }}</p>
                        <p class="text-xs text-gray-500">Research</p>
                    </div>
                    <div class="bg-blue-50 rounded-xl p-3">
                        <p class="text-2xl font-bold text-blue-700">{{ $college->users_count }}</p>
                        <p class="text-xs text-gray-500">Users</p>
                    </div>
                </div>
                @if($college->dean)
                <p class="text-xs text-gray-500 mb-1"><i class="fas fa-user-tie mr-1 text-orange-400"></i> {{ $college->dean }}</p>
                @endif
                @if($college->contact_email)
                <p class="text-xs text-gray-500 mb-3"><i class="fas fa-envelope mr-1 text-orange-400"></i> {{ $college->contact_email }}</p>
                @endif
                <div class="flex gap-2">
                    <a href="{{ route('colleges.edit', $college->id) }}" class="flex-1 text-center text-xs bg-blue-50 text-blue-700 py-2 rounded-lg hover:bg-blue-100 font-semibold">
                        <i class="fas fa-edit mr-1"></i> Edit
                    </a>
                    <form action="{{ route('colleges.destroy', $college->id) }}" method="POST" onsubmit="return confirm('Delete this college?')">
                        @csrf
                        @method('DELETE')
                        <button class="text-xs bg-red-50 text-red-700 px-4 py-2 rounded-lg hover:bg-red-100"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-3 text-center py-16 bg-white rounded-2xl border border-gray-100">
            <i class="fas fa-university text-5xl text-gray-300 mb-3"></i>
            <p class="text-gray-500">No colleges found. <a href="{{ route('colleges.create') }}" class="text-orange-600 font-semibold">Add one.</a></p>
        </div>
        @endforelse
    </div>
    <div>{{ $colleges->links() }}</div>
</div>
@endsection
