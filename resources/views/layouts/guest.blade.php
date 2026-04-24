<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Setup Syarikat') — SAGA SME</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-50">

    {{-- Header --}}
    <div class="bg-white border-b border-gray-100 px-6 py-4">
        <div class="max-w-2xl mx-auto flex items-center justify-between">
            <h1 class="text-lg font-bold text-indigo-600">SAGA SME</h1>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="text-sm text-gray-400 hover:text-gray-600">Log Keluar</button>
            </form>
        </div>
    </div>

    <div class="max-w-2xl mx-auto py-10 px-4">

        {{-- Progress Bar — only show during onboarding --}}
        @isset($currentStep)
        <div class="mb-8">
            <div class="flex items-center justify-between mb-2">
                @foreach(['Profil Syarikat', 'Maklumat Cukai', 'Pelan Akaun'] as $i => $label)
                    @php $stepNum = $i + 1; @endphp
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium
                            {{ $currentStep > $stepNum ? 'bg-indigo-600 text-white' :
                               ($currentStep == $stepNum ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-500') }}">
                            {{ $currentStep > $stepNum ? '✓' : $stepNum }}
                        </div>
                        <span class="text-xs mt-1 {{ $currentStep == $stepNum ? 'text-indigo-600 font-medium' : 'text-gray-400' }}">
                            {{ $label }}
                        </span>
                    </div>
                    @if($i < 2)
                        <div class="flex-1 h-0.5 mx-2 mt-[-12px]
                            {{ $currentStep > $stepNum ? 'bg-indigo-600' : 'bg-gray-200' }}">
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        @endisset

        {{-- Flash --}}
        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        {{-- Card --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
            @yield('content')
        </div>

    </div>

</body>
</html>
