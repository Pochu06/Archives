@extends('layouts.app')
@section('title', 'Create User')
@section('page-title', 'Create User')
@section('page-subtitle', 'Add a new user to the system')
@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-orange-600 to-orange-800 p-6 text-white">
            <h2 class="text-xl font-bold"><i class="fas fa-user-plus mr-2"></i>Create New User</h2>
        </div>
        <form action="{{ route('users.store') }}" method="POST" class="p-8 space-y-5">
            @csrf
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Full Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500 @error('name') border-red-400 @enderror">
                @error('name')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Email <span class="text-red-500">*</span></label>
                <input type="email" name="email" value="{{ old('email') }}" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500 @error('email') border-red-400 @enderror">
                @error('email')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Role <span class="text-red-500">*</span></label>
                    <select name="role" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500">
                        @if(session('user_role') === 'super_admin')
                        <option value="super_admin" {{ old('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                        <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                        @endif
                        <option value="student" {{ old('role') === 'student' ? 'selected' : '' }}>Student</option>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">College <span class="text-red-500">*</span></label>
                    <select name="college_id" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500">
                        <option value="">Select College</option>
                        @foreach($colleges as $college)
                        <option value="{{ $college->id }}" {{ old('college_id') == $college->id ? 'selected' : '' }}>{{ $college->code }}</option>
                        @endforeach
                    </select>
                    @error('college_id')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Student ID</label>
                <input type="text" name="student_id" value="{{ old('student_id') }}" placeholder="e.g., 2021-00001" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Password <span class="text-red-500">*</span></label>
                    <input type="password" name="password" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500 @error('password') border-red-400 @enderror">
                    @error('password')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Confirm Password <span class="text-red-500">*</span></label>
                    <input type="password" name="password_confirmation" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500">
                </div>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('users.index') }}" class="bg-gray-100 text-gray-700 px-6 py-3 rounded-xl font-semibold hover:bg-gray-200 transition">Cancel</a>
                <button type="submit" class="bg-gradient-to-r from-orange-600 to-orange-700 text-white px-8 py-3 rounded-xl font-bold hover:from-orange-700 hover:to-orange-800 transition shadow">
                    <i class="fas fa-user-plus mr-1"></i> Create User
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
