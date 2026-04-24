@extends('layouts.guest')
@section('title', 'Sahkan Email â€” SAGA SME')

@section('content')
<div class="text-center">
    <div class="text-5xl mb-4">í³§</div>
    <h2 class="text-xl font-semibold text-gray-800 mb-2">Sahkan Email Anda</h2>
    <p class="text-sm text-gray-500 mb-6">
        Kami telah menghantar pautan pengesahan ke email anda.
        Sila semak inbox (atau folder spam).
    </p>

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit"
            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition">
            Hantar Semula Email
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4">
        @csrf
        <button type="submit" class="text-sm text-gray-400 hover:text-gray-600">
            Log Keluar
        </button>
    </form>
</div>
@endsection
