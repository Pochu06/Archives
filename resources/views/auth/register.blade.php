@extends('layouts.app')
@section('title', 'Register - Research Archive')
@section('auth-content')
<div class="min-h-screen flex items-center justify-center py-12 px-4 bg-orange-100">
    <div class="bg-white rounded-3xl shadow-2xl p-10 w-full max-w-lg">
        <div class="text-center mb-8">
            <div class="bg-orange-600 p-4 rounded-2xl inline-block mb-4">
                <i class="fas fa-user-plus text-white text-3xl"></i>
            </div>
            <h2 class="text-3xl font-bold text-gray-800">Create Account</h2>
            <p class="text-gray-500 mt-1">Join the Research Archive as a Student</p>
        </div>
        <form action="{{ route('register.post') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2 text-sm">Full Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="Juan Dela Cruz"
                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500 @error('name') border-red-400 @enderror">
                    @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2 text-sm">Student ID</label>
                    <input type="text" name="student_id" value="{{ old('student_id') }}" placeholder="2021-00001"
                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500">
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 font-semibold mb-2 text-sm">Email Address</label>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="your@email.com"
                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500 @error('email') border-red-400 @enderror">
                @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 font-semibold mb-2 text-sm">College</label>
                <select name="college_id" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500 @error('college_id') border-red-400 @enderror">
                    <option value="">Select your college</option>
                    @foreach($colleges as $college)
                    <option value="{{ $college->id }}" {{ old('college_id') == $college->id ? 'selected' : '' }}>{{ $college->code }} - {{ $college->name }}</option>
                    @endforeach
                </select>
                @error('college_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2 text-sm">Password</label>
                    <input type="password" name="password" placeholder="Min. 8 characters"
                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500 @error('password') border-red-400 @enderror">
                    @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2 text-sm">Confirm Password</label>
                    <input type="password" name="password_confirmation" placeholder="Repeat password"
                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500">
                </div>
            </div>
            <button type="submit" class="w-full bg-orange-600 text-white py-3.5 rounded-xl font-bold text-lg hover:bg-orange-700 transition shadow-lg">
                <i class="fas fa-user-plus mr-2"></i> Create Account
            </button>
        </form>
        <p class="text-center text-gray-600 mt-6 text-sm">
            Already have an account? <a href="{{ route('login') }}" class="text-orange-600 font-semibold hover:underline">Login here</a>
        </p>
    </div>
</div>
@endsection

