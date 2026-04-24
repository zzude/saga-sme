@extends('layouts.guest')
@section('title', 'Profil Syarikat')

@section('content')
<h2 class="text-xl font-semibold text-gray-800 mb-1">Profil Syarikat</h2>
<p class="text-sm text-gray-500 mb-6">Lengkapkan maklumat asas syarikat anda.</p>

<form method="POST" action="{{ route('onboarding.step', ['step' => 1]) }}">
    @csrf

    <div class="grid grid-cols-2 gap-4 mb-4">
        <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">No. Pendaftaran SSM</label>
            <input type="text" name="registration_number"
                value="{{ old('registration_number', $company->registration_number) }}"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                placeholder="202301012345">
        </div>

        <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">No. Telefon</label>
            <input type="text" name="phone"
                value="{{ old('phone', $company->phone) }}"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                placeholder="088-123456">
        </div>

        <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
            <textarea name="address" rows="2"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                placeholder="No. 1, Jalan Contoh">{{ old('address', $company->address) }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Bandar</label>
            <input type="text" name="city"
                value="{{ old('city', $company->city) }}"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                placeholder="Kota Kinabalu">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Poskod</label>
            <input type="text" name="postcode"
                value="{{ old('postcode', $company->postcode) }}"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                placeholder="88000">
        </div>

        <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Negeri</label>
            <select name="state"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                <option value="">-- Pilih Negeri --</option>
                @foreach(['Sabah','Sarawak','Selangor','Kuala Lumpur','Johor','Kedah','Kelantan',
                          'Melaka','Negeri Sembilan','Pahang','Perak','Perlis','Pulau Pinang',
                          'Putrajaya','Labuan','Terengganu'] as $state)
                    <option value="{{ $state }}"
                        {{ old('state', $company->state) == $state ? 'selected' : '' }}>
                        {{ $state }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Tarikh Mula Tahun Kewangan
            </label>
            <input type="date" name="financial_year_start"
                value="{{ old('financial_year_start', $company->financial_year_start?->format('Y-m-d')) }}"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
            <p class="text-xs text-gray-400 mt-1">Contoh: 2026-01-01 untuk Jan–Dis</p>
        </div>
    </div>

    <div class="flex justify-end">
        <button type="submit"
            class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-6 rounded-lg text-sm transition">
            Seterusnya →
        </button>
    </div>
</form>
@endsection
