@php use App\Models\Project;use Illuminate\Database\Eloquent\Model; @endphp
    <!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Fokus - Modern Project Management</title>

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://usefokus.com/">
    <meta property="og:title" content="Fokus - Modern Project Management">
    <meta property="og:description" content="Fokus is a project management tool that helps you focus on what matters.">
    <meta property="og:image" content="{{ asset('/asset/images/og/usefokus-sprint-overview.png') }}">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://usefokus.com/">
    <meta property="twitter:title" content="Fokus - Modern Project Management">
    <meta property="twitter:description" content="Fokus is a project management tool that helps you focus on what matters.">
    <meta property="twitter:image" content="{{ asset('/asset/images/og/usefokus-sprint-overview.png') }}">

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="/asset/images/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/asset/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/asset/images/favicon/favicon-16x16.png">
    <link rel="manifest" href="/asset/images/favicon/site.webmanifest">
    <link rel="mask-icon" href="/asset/images/favicon/safari-pinned-tab.svg" color="#ff2d20">
    <link rel="shortcut icon" href="/asset/images/favicon/favicon.ico">
    <meta name="msapplication-TileColor" content="#ff2d20">
    <meta name="msapplication-config" content="/asset/images/favicon/browserconfig.xml">
    <meta name="theme-color" content="#ffffff">
    <meta name="color-scheme" content="light">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="min-h-screen font-sans antialiased bg-base-200">

{{-- NAVBAR mobile only --}}
<x-nav sticky class="lg:hidden">
    <x-slot:brand>
        <x-app-brand/>
    </x-slot:brand>
    <x-slot:actions>
        <label for="main-drawer" class="lg:hidden me-3">
            <x-icon name="o-bars-3" class="cursor-pointer"/>
        </label>
    </x-slot:actions>
</x-nav>

{{-- MAIN --}}
<x-main>
    {{-- SIDEBAR --}}
    <x-slot:sidebar drawer="main-drawer" collapsible class="bg-base-100 lg:bg-inherit">

        {{-- BRAND --}}
        <div class="px-5 pt-4 flex items-center gap-2">
            <x-icon name="o-cube" class="text-primary w-8 h-8"/>
            <div>
                <h1 class="text-xl font-bold text-primary">Fokus</h1>
                <p class="text-xs">Project Management</p>
            </div>
        </div>

        {{-- Current Workspace --}}
        @if(session('workspace_id'))
            @php
                $currentWorkspace = \App\Models\Workspace::find(session('workspace_id'));
            @endphp
            @if($currentWorkspace)
                <div class="px-5 py-2 bg-primary/10 mx-2 rounded-lg mt-2">
                    <p class="text-xs text-gray-500">CURRENT WORKSPACE</p>
                    <p class="font-medium">{{ $currentWorkspace->name }}</p>
                    <a href="{{ route('workspaces.index') }}" onclick="event.preventDefault(); document.getElementById('change-workspace-form').submit();" class="btn btn-xs btn-ghost mt-1">
                        <x-icon name="fas.exchange-alt" class="w-3 h-3"/>
                        Change
                    </a>
                    <form id="change-workspace-form" action="{{ route('workspaces.index') }}" method="GET" style="display: none;">
                        <input type="hidden" name="reset_workspace" value="1">
                    </form>
                </div>
            @endif
        @endif

        {{-- MENU --}}
        <x-menu activate-by-route>

            {{-- User --}}
            @if($user = auth()->user())
                <x-menu-separator/>

                <x-list-item :item="$user" value="name" sub-value="email" no-separator no-hover
                             class="-mx-2 !-my-2 rounded">
                    <x-slot:actions>
                        <x-button icon="o-power" class="btn-circle btn-ghost btn-xs" tooltip-left="Logout"
                                  no-wire-navigate link="/logout"/>
                    </x-slot:actions>
                </x-list-item>

                <x-menu-separator/>
            @endif

            <x-menu-item title="Dashboard" icon="o-home" link="/dashboard"/>
            <x-menu-item title="Projects" icon="o-folder" link="/projects"/>
            <x-menu-item title="Assets" icon="fas.box" link="/assets"/>
            <x-menu-item title="Licenses" icon="fas.key" link="/licenses"/>
            <x-menu-item title="HR" icon="fas.users" :link="route('hr.dashboard')"/>
         {{--   <x-menu-item title="Gamification" icon="fas.trophy" :link="route('gamification.dashboard')"/>--}}
           {{-- <x-menu-item title="API Tester" icon="fas.calendar" link="/api-tester"/>--}}
            <x-menu-item title="Wiki" icon="fas.book" link="/wiki"/>
            <x-menu-item title="Vaults" icon="fas.landmark" :link="route('password-manager.dashboard')"/>

           {{-- <x-menu-item title="Mail" icon="o-envelope" link="/mail"/>--}}
            @if(request()->routeIs('projects.show') || request()->routeIs('projects.edit') ||
                request()->routeIs('tasks.*') || request()->routeIs('sprints.*') || request()->routeIs('board.*')
                || request()->routeIs('projects.*') || request()->routeIs('wiki.*') ||
                 request()->routeIs('api-tester.*'))
                @php
                    $project = request()->route('project');

                if (!$project instanceof Model) {
                    $project = Project::find($project);
                    }

                if ((request()->filled('selectedProjectId') && !in_array(request()->input('selectedProjectId'), ['all', 'none']))) {
                    $project = Project::find(request()->input('selectedProjectId'));
                }
                @endphp

                @if($project)
                    <x-menu-separator/>

                    <div class="px-4 py-2">
                        <p class="text-xs text-gray-500">CURRENT PROJECT</p>
                        <p class="font-medium">{{ $project->name }}</p>
                    </div>

                    <x-menu-item title="Project Overview" icon="o-document-text" link="/projects/{{ $project->id }}"/>
                    <x-menu-item title="Board" icon="o-view-columns" link="/projects/{{ $project->id }}/board"/>
                    <x-menu-item title="Tasks" icon="o-clipboard-document-list"
                                 link="/projects/{{ $project->id }}/tasks"/>
                    <x-menu-item title="Sprints" icon="fas.calendar" link="/projects/{{ $project->id }}/sprints"/>
                  {{--  <x-menu-item title="API Tester" icon="fas.calendar" link="/api-tester"/>--}}
                    <x-menu-item title="Meetings" icon="fas.calendar-alt" link="/projects/{{ $project->id }}/meetings"/>
                    <x-menu-item title="Wiki" icon="fas.book" link="/projects/{{ $project->id }}/wiki"/>
                   {{-- <x-menu-item title="Files" icon="fas.folder" link="/projects/{{ $project->id }}?tab=files"/>--}}
                  {{--  <x-menu-item title="Gantt Chart" icon="fas.chart-gantt"
                                 :link="route('tasks.gantt-chart', $project)"/>--}}
                @endif
            @endif

            <x-menu-separator/>

            <x-menu-item title="Profile" icon="o-user" link="/profile"/>

            @guest
                <x-menu-item title="Login" icon="o-arrow-right-on-rectangle" link="/login"/>
                <x-menu-item title="Register" icon="o-user-plus" link="/register"/>
            @endguest
        </x-menu>
    </x-slot:sidebar>

    {{-- The `$slot` goes here --}}
    <x-slot:content>
        @if(session('message'))
            <div class="alert alert-success shadow-lg max-w-md mx-auto mt-4">
                <x-icon name="o-check-circle" class="w-6 h-6"/>
                <span>{{ session('message') }}</span>
            </div>
        @endif

        {{ $slot }}
    </x-slot:content>
</x-main>

{{--  TOAST area --}}
<x-toast/>

{{-- Stack for page specific scripts --}}
@stack('scripts')
</body>
</html>
