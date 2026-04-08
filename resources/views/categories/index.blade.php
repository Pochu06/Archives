@extends('layouts.app')
@section('title', 'Categories')
@section('page-title', 'Research Categories')
@section('page-subtitle', 'Manage research paper categories')
@section('content')
<div class="space-y-5">
    <div class="flex justify-end">
        <a href="{{ route('categories.create') }}" class="bg-gradient-to-r from-orange-600 to-orange-700 text-white px-5 py-2.5 rounded-xl font-semibold hover:from-orange-700 hover:to-orange-800 transition shadow">
            <i class="fas fa-plus mr-1"></i> Add Category
        </a>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-orange-50 border-b border-orange-100">
                <tr>
                    <th class="px-6 py-3.5 text-left text-xs font-bold text-orange-800 uppercase">Category Name</th>
                    <th class="px-6 py-3.5 text-left text-xs font-bold text-orange-800 uppercase">Description</th>
                    <th class="px-6 py-3.5 text-left text-xs font-bold text-orange-800 uppercase">Research Count</th>
                    <th class="px-6 py-3.5 text-right text-xs font-bold text-orange-800 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($categories as $cat)
                <tr class="hover:bg-orange-50/20">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="bg-orange-100 p-2 rounded-lg mr-3"><i class="fas fa-tag text-orange-600 text-sm"></i></div>
                            <span class="font-semibold text-gray-800">{{ $cat->name }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $cat->description ?? '—' }}</td>
                    <td class="px-6 py-4">
                        <span class="bg-orange-100 text-orange-800 text-sm font-bold px-3 py-1 rounded-full">{{ $cat->research_count }}</span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('categories.edit', $cat->id) }}" class="text-xs bg-blue-50 text-blue-700 px-3 py-1.5 rounded-lg hover:bg-blue-100 font-medium">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('categories.destroy', $cat->id) }}" method="POST" onsubmit="return confirm('Delete this category?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-xs bg-red-50 text-red-700 px-3 py-1.5 rounded-lg hover:bg-red-100"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-6 py-12 text-center text-gray-500">No categories found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $categories->links() }}</div>
</div>
@endsection
