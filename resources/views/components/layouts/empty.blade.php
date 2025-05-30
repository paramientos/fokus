<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans antialiased bg-base-200/50 dark:bg-base-200">
{{-- You could elaborate the layout here --}}
{{-- The important part is to have a different layout from the main app layout --}}
<x-main-line full-width>
    <x-slot:content>
        {{ $slot }}
    </x-slot:content>
</x-main-line>
</body>
</html>
