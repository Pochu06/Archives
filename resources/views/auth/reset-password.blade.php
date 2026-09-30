@extends('layouts.app')

@section('title', 'Reset Password - Research Archive')

@section('auth-content')
<div class="min-h-screen flex items-center justify-center py-12 px-4 bg-orange-100">
    <div class="bg-white rounded-3xl shadow-2xl p-10 w-full max-w-md">
        <div class="text-center mb-8">
            <div class="bg-orange-600 p-4 rounded-2xl inline-block mb-4">
                <i class="fas fa-lock text-white text-3xl"></i>
            </div>
            <h2 class="text-3xl font-bold text-gray-800">Reset Password</h2>
            <p class="text-gray-500 mt-1">Choose a new password for your account.</p>
        </div>
        <form action="{{ route('password.update') }}" method="POST">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="mb-4">
                <label class="block text-gray-700 font-semibold mb-2 text-sm">Email Address</label>
                <input type="email" name="email" value="{{ old('email', $email) }}" required
                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500 @error('email') border-red-400 @enderror">
                @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 font-semibold mb-2 text-sm">New Password</label>
                <input type="password" name="password" required autocomplete="new-password" placeholder="8+ chars, upper, number, symbol"
                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500 @error('password') border-red-400 @enderror">
                @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 font-semibold mb-2 text-sm">Confirm Password</label>
                <input type="password" name="password_confirmation" required autocomplete="new-password"
                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500">
            </div>
            <button type="submit" class="w-full bg-orange-600 text-white py-3.5 rounded-xl font-bold hover:bg-orange-700 transition shadow-lg">
                <i class="fas fa-save mr-2"></i> Reset Password
            </button>
        </form>
    </div>
</div>
@endsection