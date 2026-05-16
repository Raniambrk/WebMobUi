<x-vue-app-layout>
    <x-slot:title>
        Voter
    </x-slot>

    <x-slot:scripts>
        @vite(['resources/js/poll-vote.js'])
    </x-slot>

    @php
        $pageProps = json_encode([
            'token' => $token,
            'loginUrl' => route('login'),
            'isAuthenticated' => auth()->check(),
        ]);
    @endphp

    <div id="app-vote" data-props="{{ $pageProps }}"></div>
</x-vue-app-layout>