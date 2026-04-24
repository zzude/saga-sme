@extends('layouts.guest')
@section('title', 'Maklumat Cukai')

@section('content')
<h2 class="text-xl font-semibold text-gray-800 mb-1">Maklumat Cukai & Pematuhan</h2>
<p class="text-sm text-gray-500 mb-6">Maklumat ini digunakan untuk e-Invoice dan SST.</p>

<form method="POST" action="{{ route('onboarding.step', ['step' => 2]) }}">
    @csrf

    <div class="space-y-4 mb-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                TIN — No. Cukai Pendapatan
            </label>
            <input type="text" name="tax_number"
                value="{{ old('tax_number', $company->tax_number) }}"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                placeholder="C12345678900">
            <p class="text-xs text-gray-400 mt-1">Diperlukan untuk e-Invoice MyInvois.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                No. Pendaftaran SST <span class="text-gray-400 font-normal">(jika ada)</span>
            </label>
            <input type="text" name="sst_number"
                value="{{ old('sst_number', $company->sst_number) }}"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                placeholder="W10-1234-12345678">
            <p class="text-xs text-gray-400 mt-1">Wajib jika pendapatan melebihi RM500,000/tahun.</p>
        </div>
    </div>

    <div class="flex justify-between">
        <a href="{{ route('onboarding.step', ['step' => 1]) }}"
            class="text-sm text-gray-500 hover:text-gray-700 py-2">
            ← Kembali
        </a>
        <button type="submit"
            class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-6 rounded-lg text-sm transition">
            Seterusnya →
        </button>
    </div>
</form>
@endsection
