@extends('layouts.app')

@section('title', 'Login - Research Archive')

@section('auth-content')
<div class="min-h-screen bg-gray-100">
    <div class="min-h-screen lg:grid lg:grid-cols-2">
        <div class="hidden lg:flex items-center justify-center bg-orange-600 p-12 xl:p-16">
            <div class="text-white text-center max-w-lg">
                <div class="bg-white/15 border border-white/20 p-8 rounded-3xl mb-8">
                    <x-archives-logo class="mx-auto mb-4 h-24 w-24 object-contain brightness-0 invert" />
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
                    <x-archives-logo class="mx-auto mb-3 h-14 w-14 object-contain" />
                    <p class="text-sm text-gray-500">Research Archive System</p>
                </div>

            <div class="text-center mb-8">
                <div class="bg-orange-600 p-4 rounded-2xl inline-block mb-4">
                    <i class="fas fa-lock text-white text-3xl"></i>
                </div>
                <h2 class="text-3xl font-bold text-gray-800">Welcome Back</h2>
                <p class="text-gray-500 mt-1">Sign in to your account</p>
            </div>

            <form action="{{ route('login.post') }}" method="POST">
                @csrf
                <div class="mb-5">
                    <label class="block text-gray-700 font-semibold mb-2 text-sm">Email Address</label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-4 top-3.5 text-gray-400"></i>
                        <input type="email" name="email" value="{{ old('email', session('verification_email')) }}" placeholder="your@email.com"
                            class="w-full pl-11 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500 transition @error('email') border-red-400 @enderror">
                    </div>
                    @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2 text-sm">Password</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-4 top-3.5 text-gray-400"></i>
                        <input type="password" name="password" placeholder="••••••••"
                            class="w-full pl-11 pr-12 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-500 transition @error('password') border-red-400 @enderror">
                        <button type="button" data-password-toggle aria-label="Show password" aria-pressed="false"
                            class="absolute right-3 top-1/2 -translate-y-1/2 rounded p-2 text-gray-500 hover:text-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500">
                            <i class="fas fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                    @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="mb-5 flex justify-center">
                    <div class="w-full max-w-[300px] flex flex-col items-center text-center">
                        @if (config('services.recaptcha.site_key'))
                            <div class="g-recaptcha mx-auto" data-sitekey="{{ config('services.recaptcha.site_key') }}" data-callback="enableLoginButton" data-expired-callback="disableLoginButton" data-error-callback="disableLoginButton"></div>
                        @else
                            <p class="text-red-600 text-sm">CAPTCHA is not configured. Please contact the administrator.</p>
                        @endif
                        @error('g-recaptcha-response')<p class="text-red-500 text-xs mt-1 text-center">{{ $message }}</p>@enderror
                    </div>
                </div>
                @if (session('status'))
                    <p class="text-green-600 text-sm mb-4">{{ session('status') }}</p>
                @endif
                <button id="loginSubmitButton" type="submit" disabled class="w-full bg-orange-600 text-white py-3.5 rounded-xl font-bold text-lg hover:bg-orange-700 transition shadow-lg disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-orange-600">
                    <i class="fas fa-sign-in-alt mr-2"></i> Sign In
                </button>
            </form>
            @php($resendEmail = session('verification_email') ?? (str_contains($errors->first('email'), 'verify your email') ? old('email') : null))
            @if ($resendEmail)
                <form action="{{ route('verification.send') }}" method="POST" class="mt-4 text-center text-sm text-gray-600">
                    @csrf
                    <input type="hidden" name="email" value="{{ $resendEmail }}">
                    Can't find the verification email (or deleted it)?
                    <button type="submit" class="text-orange-600 font-semibold hover:underline">Resend verification email</button>
                </form>
            @endif
            <script>
                window.enableLoginButton = (token) => {
                    document.getElementById('loginSubmitButton').disabled = !token;
                };
                window.disableLoginButton = () => {
                    document.getElementById('loginSubmitButton').disabled = true;
                };
            </script>
            <script src="https://www.google.com/recaptcha/api.js" async defer></script>
            <p class="text-center text-sm mt-4"><a href="{{ route('password.request') }}" class="text-orange-600 font-semibold hover:underline">Forgot your password?</a></p>
            <p class="text-center text-gray-600 mt-6 text-sm">
                Don't have an account? <a href="{{ route('register') }}" class="text-orange-600 font-semibold hover:underline">Register here</a>
            </p>
        </div>
    </div>
</div>
</div>
@endsection
