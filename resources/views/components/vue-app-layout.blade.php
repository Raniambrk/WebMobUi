@props([
    'bodyClass' => 'min-h-screen bg-slate-50 text-slate-900 antialiased',
])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8">
    @isset($description)
<meta name="description" content="{{ $description }}">
    @endisset
<meta name="viewport" content="width=device-width, initial-scale=1">
    @isset($title)
<title>{{ $title }} - {{ config('app.name') }}</title>
    @else
<title>{{ config('app.name') }}</title>
    @endisset
    @vite(['resources/css/app.css'])
    @isset($scripts)
        {{ $scripts }}
    @endisset
</head>
<body {{ $attributes->class([$bodyClass]) }}>

    {{-- Ajout : barre de navigation minimale pour revenir à l'app principale --}}
    <nav class="bg-teal-600 text-white px-4 py-3 flex items-center gap-4 text-sm">
        <a href="{{ url('/') }}" class="hover:opacity-80 font-medium">
            {{ config('app.name') }}
        </a>
        <a href="{{ url('/posts') }}" class="bg-teal-700 px-3 py-1 rounded-md hover:bg-teal-800">
            Tous les posts
        </a>
        @auth
        <a href="{{ url('/polls/dashboard') }}" class="bg-teal-700 px-3 py-1 rounded-md hover:bg-teal-800">
            Sondages
        </a>
        <a href="{{ url('/my-profile') }}" class="ml-auto hover:opacity-80">
            {{ Auth::user()->username }}
        </a>
        @endauth
    </nav>

    {{ $slot }}
</body>
</html>
