@extends('layouts.app')

@section('title', 'Login - Research Archive')

@section('auth-content')
<div class="min-h-screen bg-gray-100">
    <div class="min-h-screen lg:grid lg:grid-cols-2">
        <div class="hidden lg:flex items-center justify-center bg-orange-600 p-12 xl:p-16">
            <div class="text-white text-center max-w-lg">
                <div class="bg-white/15 border border-white/20 p-8 rounded-3xl mb-8">
                    <i class="fas fa-book-open text-6xl text-white mb-4"></i>
                    <h1 class="text-4xl font-extrabold mb-2">Research Archive</h1>
                    <p class="text-lg text-orange-100">Repository System</p>
                </div>
                <p class="text-orange-100 text-lg leading-relaxed">Your centralized platform for academic research submission, management, and archiving across all colleges.</p>
                <div class="mt-8 grid grid-cols-3 gap-4 text-center">
                    <div class="bg-white/15 border border-white/20 rounded-xl p-4">
                        <i class="fas fa-file-alt text-white text-2xl mb-2"></i>
                        <p class="text-sm font-semibold">Research Papers</p>
                    </div>
                    <div class="bg-white/15 border border-white/20 rounded-xl p-4">
                        <i class="fas fa-university text-white text-2xl mb-2"></i>
                        <p class="text-sm font-semibold">7 Colleges</p>
                    </div>
                    <div class="bg-white/15 border border-white/20 rounded-xl p-4">
                        <i class="fas fa-users text-white text-2xl mb-2"></i>
                        <p class="text-sm font-semibold">Multi-Role</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-center p-5 sm:p-8 lg:p-12 bg-white">
            <div class="bg-white border border-gray-200 rounded-3xl shadow-xl p-8 sm:p-10 w-full max-w-md">
                <div class="lg:hidden text-center mb-6">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-orange-600 text-white mb-3">
                        <i class="fas fa-book-open text-2xl"></i>
                    </div>
                    <p class="text-sm text-gray-500">Research Archive System</p>
                </div>

            <div class="text-center mb-8">
                <div class="bg-orange-600 p-4 rounded-2xl inline-block mb-4">
                    <i class="fas fa-lock text-white text-3xl"></i>
                </div>
                <h2 class="text-3xl font-bold text-gray-800">Welcome Back</h2>
                <p class="text-gray-500 mt-1">Sign in to your account</p>
            </div>

            <!-- Demo Credentials -->
            <div class="bg-orange-50 border border-orange-200 rounded-xl p-4 mb-6">
                <p class="text-orange-800 font-semibold text-sm mb-2"><i class="fas fa-info-circle mr-1"></i> Demo Credentials</p>
                <div class="space-y-1 text-xs text-orange-700">
                    <p><strong>Super Admin:</strong> superadmin@university.edu.ph / password123</p>
                    <p><strong>Admin:</strong> cics.admin@university.edu.ph / password123</p>
                    <p><strong>Adviser:</strong> dr.lornareyes@university.edu.ph / password123</p>
                    <p><strong>Student:</strong> juan.delacruz@student.university.edu.ph / password123</p>
                </div>
            </div>

            <form action="{{ route('login.post') }}" method="POST">
                @csrf
                <div class="mb-5">
                    <label class="block text-gray-700 font-semibold mb-2 text-sm">Email Address</label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-4 top-3.5 text-gray-400"></i>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="your@email.com"
                            class="w-full pl-11 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500 transition @error('email') border-red-400 @enderror">
                    </div>
                    @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2 text-sm">Password</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-4 top-3.5 text-gray-400"></i>
                        <input type="password" name="password" placeholder="••••••••"
                            class="w-full pl-11 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500 transition @error('password') border-red-400 @enderror">
                    </div>
                    @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="w-full bg-orange-600 text-white py-3.5 rounded-xl font-bold text-lg hover:bg-orange-700 transition shadow-lg">
                    <i class="fas fa-sign-in-alt mr-2"></i> Sign In
                </button>
            </form>
            <p class="text-center text-gray-600 mt-6 text-sm">
                Don't have an account? <a href="{{ route('register') }}" class="text-orange-600 font-semibold hover:underline">Register here</a>
            </p>
        </div>
    </div>
</div>
</div>
@endsection
