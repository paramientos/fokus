<?php

namespace App\Http\Controllers;

use App\Models\GitRepository;
use App\Models\Project;
use App\Services\GitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GitSSOController extends Controller
{
    protected $gitService;

    public function __construct(GitService $gitService)
    {
        $this->gitService = $gitService;
    }

    /**
     * Redirect to OAuth provider for authentication.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirect(Request $request, string $provider, int $projectId)
    {
        try {
            $project = Project::findOrFail($projectId);

            // Check if user has access to the project
            if ($project->workspace_id !== get_workspace_id()) {
                return redirect()->back()->with('error', 'You do not have access to this project.');
            }

            session(['git_sso_project_id' => $projectId]);

            // Generate OAuth URL
            $redirectUrl = route('git.sso.callback', ['provider' => $provider]);

            $authUrl = $this->gitService->connectWithSSO($provider, $redirectUrl);

            return redirect()->away($authUrl);
        } catch (\Exception $e) {
            Log::error('Failed to redirect to OAuth provider: '.$e->getMessage(), [
                'provider' => $provider,
                'project_id' => $projectId,
                'exception' => $e,
            ]);

            return redirect()->back()->with('error', 'Failed to connect with '.ucfirst($provider).'. Please try again.');
        }
    }

    public function callback(Request $request, string $provider)
    {
        try {
            $projectId = session('git_sso_project_id');

            $project = Project::findOrFail($projectId);

            // Check if user has access to the project
            if ($project->workspace_id !== get_workspace_id()) {
                return redirect()->route('projects.show', $projectId)->with('error', 'You do not have access to this project.');
            }

            // Handle the OAuth callback
            $redirectUrl = route('git.sso.callback', ['provider' => $provider]);

            // Prepare callback data for the service
            $callbackData = [
                'code' => $request->code,
                'state' => $request->state,
                'redirect_uri' => $redirectUrl,
            ];

            // Call the service with the correct parameters
            $gitRepository = $this->gitService->handleSSOCallback($provider, $callbackData, $project->id);

            if (!$gitRepository) {
                return redirect()->route('projects.edit', $project)
                    ->with('error', 'Failed to connect with '.ucfirst($provider).': Invalid response from provider');
            }

            return redirect()->route('projects.edit', $project)
                ->with('success', ucfirst($provider).' repository connected successfully: '.$gitRepository->name);
        } catch (\Exception $e) {
            Log::error('Failed to handle OAuth callback: '.$e->getMessage(), [
                'provider' => $provider,
                'project_id' => $projectId,
                'code' => $request->code,
                'exception' => $e,
            ]);

            return redirect()->route('projects.edit', $projectId)
                ->with('error', 'Failed to connect with '.ucfirst($provider).': '.$e->getMessage());
        }
    }

    /**
     * Disconnect a Git repository.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function disconnect(Request $request, int $repositoryId)
    {
        try {
            $repository = GitRepository::findOrFail($repositoryId);
            $projectId = $repository->project_id;

            // Check if user has access to the project
            if ($repository->workspace_id !== auth()->user()->current_workspace_id) {
                return redirect()->back()->with('error', 'You do not have access to this repository.');
            }

            // Delete the repository
            $repositoryName = $repository->name;
            $repository->delete();

            return redirect()->back()->with('success', 'Git repository disconnected: '.$repositoryName);
        } catch (\Exception $e) {
            Log::error('Failed to disconnect repository: '.$e->getMessage(), [
                'repository_id' => $repositoryId,
                'exception' => $e,
            ]);

            return redirect()->back()->with('error', 'Failed to disconnect repository: '.$e->getMessage());
        }
    }
}
