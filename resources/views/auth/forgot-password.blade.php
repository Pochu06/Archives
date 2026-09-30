@extends('layouts.app')

@section('title', 'Forgot Password - Research Archive')

@section('auth-content')
<div class="min-h-screen flex items-center justify-center py-12 px-4 bg-orange-100">
    <div class="bg-white rounded-3xl shadow-2xl p-10 w-full max-w-md">
        <div class="text-center mb-8">
            <div class="bg-orange-600 p-4 rounded-2xl inline-block mb-4">
                <i class="fas fa-key text-white text-3xl"></i>
            </div>
            <h2 class="text-3xl font-bold text-gray-800">Forgot Password?</h2>
            <p class="text-gray-500 mt-1">Enter your email to receive a reset link.</p>
        </div>
        @if (session('status'))
            <p class="text-green-600 text-sm mb-4">{{ session('status') }}</p>
        @endif
        <form action="{{ route('password.email') }}" method="POST">
            @csrf
            <label class="block text-gray-700 font-semibold mb-2 text-sm">Email Address</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus
                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500 @error('email') border-red-400 @enderror">
            @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            <button type="submit" class="w-full mt-6 bg-orange-600 text-white py-3.5 rounded-xl font-bold hover:bg-orange-700 transition shadow-lg">
                <i class="fas fa-paper-plane mr-2"></i> Send Reset Link
            </button>
        </form>
        <p class="text-center text-gray-600 mt-6 text-sm"><a href="{{ route('login') }}" class="text-orange-600 font-semibold hover:underline">Back to login</a></p>
    </div>
</div>
@endsection