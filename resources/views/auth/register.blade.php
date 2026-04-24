@extends('layouts.guest')
@section('title', 'Daftar Akaun — SAGA SME')

@section('content')
<h2 class="text-xl font-semibold text-gray-800 mb-6">Daftar Akaun Baru</h2>

<form method="POST" action="{{ route('register') }}">
    @csrf

    {{-- Nama --}}
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Penuh</label>
        <input type="text" name="name" value="{{ old('name') }}"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 @error('name') border-red-400 @enderror"
            placeholder="Ahmad bin Ali">
        @error('name')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Email --}}
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Email</label>
        <input type="email" name="email" value="{{ old('email') }}"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 @error('email') border-red-400 @enderror"
            placeholder="ahmad@syarikat.com">
        @error('email')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Nama Syarikat --}}
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Syarikat</label>
        <input type="text" name="company_name" value="{{ old('company_name') }}"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 @error('company_name') border-red-400 @enderror"
            placeholder="Syarikat ABC Sdn Bhd">
        @error('company_name')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Password --}}
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Kata Laluan</label>
        <input type="password" name="password"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 @error('password') border-red-400 @enderror"
            placeholder="Minimum 8 aksara">
        @error('password')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Confirm Password --}}
    <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-1">Sahkan Kata Laluan</label>
        <input type="password" name="password_confirmation"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
            placeholder="Ulang kata laluan">
    </div>

    <button type="submit"
        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition">
        Daftar Sekarang
    </button>
</form>

<p class="text-center text-sm text-gray-500 mt-6">
    Sudah ada akaun?
    <a href="{{ route('login') }}" class="text-indigo-600 hover:underline">Log Masuk</a>
</p>
@endsection
