@extends('layouts.guest')
@section('title', 'Log Masuk — SAGA SME')

@section('content')
<h2 class="text-xl font-semibold text-gray-800 mb-6">Log Masuk</h2>

<form method="POST" action="{{ route('login') }}">
    @csrf

    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Email</label>
        <input type="email" name="email" value="{{ old('email') }}"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 @error('email') border-red-400 @enderror"
            placeholder="ahmad@syarikat.com" autofocus>
        @error('email')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-1">Kata Laluan</label>
        <input type="password" name="password"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
            placeholder="Kata laluan anda">
    </div>

    <div class="flex items-center justify-between mb-6">
        <label class="flex items-center text-sm text-gray-600">
            <input type="checkbox" name="remember" class="mr-2 rounded"> Ingat saya
        </label>
    </div>

    <button type="submit"
        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition">
        Log Masuk
    </button>
</form>

<p class="text-center text-sm text-gray-500 mt-6">
    Belum ada akaun?
    <a href="{{ route('register') }}" class="text-indigo-600 hover:underline">Daftar di sini</a>
</p>
@endsection
