@extends('layouts.guest')
@section('title', 'Pelan Akaun')

@section('content')
<h2 class="text-xl font-semibold text-gray-800 mb-1">Pelan Carta Akaun</h2>
<p class="text-sm text-gray-500 mb-6">Pilih set akaun permulaan untuk syarikat anda.</p>

<form method="POST" action="{{ route('onboarding.step', ['step' => 3]) }}">
    @csrf

    <div class="space-y-3 mb-8">
        @foreach([
            ['value' => 'standard', 'label' => 'Standard Malaysia (MPERS)',
             'desc'  => '92 akaun ‚Äî sesuai untuk kebanyakan PKS', 'recommended' => true],
            ['value' => 'minimal',  'label' => 'Minimal',
             'desc'  => '30 akaun ‚Äî untuk bisnes kecil/baru', 'recommended' => false],
            ['value' => 'extended', 'label' => 'Terperinci',
             'desc'  => '150+ akaun ‚Äî untuk operasi lebih kompleks', 'recommended' => false],
        ] as $option)
            <label class="flex items-start p-4 border-2 rounded-xl cursor-pointer transition
                {{ $option['value'] === 'standard' ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200 hover:border-indigo-200' }}">
                <input type="radio" name="coa_tier" value="{{ $option['value'] }}"
                    {{ $option['value'] === 'standard' ? 'checked' : '' }}
                    class="mt-0.5 mr-3 accent-indigo-600">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-800">{{ $option['label'] }}</span>
                        @if($option['recommended'])
                            <span class="text-xs bg-indigo-100 text-indigo-600 px-2 py-0.5 rounded-full">
                                Disyorkan
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $option['desc'] }}</p>
                </div>
            </label>
        @endforeach
    </div>

    <div class="flex justify-between">
        <a href="{{ route('onboarding.step', ['step' => 2]) }}"
            class="text-sm text-gray-500 hover:text-gray-700 py-2">
            ‚Üê Kembali
        </a>
        <button type="submit"
            class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-6 rounded-lg text-sm transition">
            Mula Guna SAGA Ì∫Ä
        </button>
    </div>
</form>
@endsection
