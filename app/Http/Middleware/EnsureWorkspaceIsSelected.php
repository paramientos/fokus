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
        if ($request->hasHeader('X-Livewire') || $request->routeIs('workspaces.*') || $request->routeIs('logout') ||  $request->routeIs('login') || $request->routeIs('profile.*') ||  $request->routeIs('register')) {
            return $next($request);
        }

        if (!session()->has('workspace_id')) {
            return redirect()->route('workspaces.index');
        }

        return $next($request);
    }
}
