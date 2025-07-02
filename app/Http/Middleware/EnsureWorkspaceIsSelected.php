<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspaceIsSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        // Skip routes that should be accessible without selecting workspace
        if ($request->hasHeader('X-Livewire') || $request->routeIs('admin.*') || $request->routeIs('workspaces.*') || $request->routeIs('landing*') || $request->routeIs('logout') ||  $request->routeIs('login') || $request->routeIs('profile.*') ||  $request->routeIs('register')) {
            return $next($request);
        }

        if (!session()->has('workspace_id')) {
            if (session()->hasAny(['info', 'warning', 'error', 'success'])) {
                return redirect()->route('workspaces.index')->with([
                    'info' => session('info'),
                    'warning' => session('warning'),
                    'error' => session('error'),
                    'success' => session('success')
                ]);
            }

            return to_route('workspaces.index');
        }

        return $next($request);
    }
}
