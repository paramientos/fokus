<?php

namespace App\Http\Controllers;

use App\Models\GitRepository;
use App\Services\GitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GitWebhookController extends Controller
{
    protected GitService $gitService;

    public function __construct(GitService $gitService)
    {
        $this->gitService = $gitService;
    }

    /**
     * Handle GitHub webhook events
     */
    public function handleGitHub(Request $request, string $token)
    {
        // Find repository by webhook token
        $repository = GitRepository::where('webhook_secret', $token)
            ->where('provider', 'github')
            ->first();

        if (!$repository) {
            return response()->json(['error' => 'Repository not found'], 404);
        }

        // Verify GitHub signature
        if (!$this->verifyGitHubSignature($request, $repository->webhook_secret)) {
            Log::warning('Invalid GitHub webhook signature', [
                'repository' => $repository->id,
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $event = $request->header('X-GitHub-Event');
        $payload = $request->json()->all();

        try {
            return match ($event) {
                'push' => $this->handlePushEvent($repository, $payload),
                'pull_request' => $this->handlePullRequestEvent($repository, $payload),
                default => response()->json(['status' => 'ignored', 'event' => $event]),
            };
        } catch (\Exception $e) {
            Log::error('Error processing GitHub webhook: '.$e->getMessage(), [
                'repository' => $repository->id,
                'event' => $event,
                'exception' => $e,
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Handle GitLab webhook events
     */
    public function handleGitLab(Request $request, string $token)
    {
        // Find repository by webhook token
        $repository = GitRepository::where('webhook_secret', $token)
            ->where('provider', 'gitlab')
            ->first();

        if (!$repository) {
            return response()->json(['error' => 'Repository not found'], 404);
        }

        // Verify GitLab token
        if ($request->header('X-Gitlab-Token') !== $repository->webhook_secret) {
            Log::warning('Invalid GitLab webhook token', [
                'repository' => $repository->id,
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Invalid token'], 403);
        }

        $event = $request->header('X-Gitlab-Event');
        $payload = $request->json()->all();

        try {
            switch ($event) {
                case 'Push Hook':
                    return $this->handlePushEvent($repository, $payload);
                case 'Merge Request Hook':
                    return $this->handlePullRequestEvent($repository, $payload);
                default:
                    return response()->json(['status' => 'ignored', 'event' => $event]);
            }
        } catch (\Exception $e) {
            Log::error('Error processing GitLab webhook: '.$e->getMessage(), [
                'repository' => $repository->id,
                'event' => $event,
                'exception' => $e,
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Handle Bitbucket webhook events
     */
    public function handleBitbucket(Request $request, string $token)
    {
        // Find repository by webhook token
        $repository = GitRepository::where('webhook_secret', $token)
            ->where('provider', 'bitbucket')
            ->first();

        if (!$repository) {
            return response()->json(['error' => 'Repository not found'], 404);
        }

        $event = $request->header('X-Event-Key');
        $payload = $request->json()->all();

        try {
            switch ($event) {
                case 'repo:push':
                    return $this->handlePushEvent($repository, $payload);
                case 'pullrequest:created':
                case 'pullrequest:updated':
                case 'pullrequest:fulfilled':
                case 'pullrequest:rejected':
                    return $this->handlePullRequestEvent($repository, $payload);
                default:
                    return response()->json(['status' => 'ignored', 'event' => $event]);
            }
        } catch (\Exception $e) {
            Log::error('Error processing Bitbucket webhook: '.$e->getMessage(), [
                'repository' => $repository->id,
                'event' => $event,
                'exception' => $e,
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Handle push events (commits)
     */
    protected function handlePushEvent(GitRepository $repository, array $payload): \Illuminate\Http\JsonResponse
    {
        // Queue a job to sync the repository
        // For simplicity, we'll just sync directly here
        $this->gitService->syncRepository($repository);

        return response()->json(['status' => 'success', 'action' => 'repository_synced']);
    }

    /**
     * Handle pull request events
     */
    protected function handlePullRequestEvent(GitRepository $repository, array $payload): \Illuminate\Http\JsonResponse
    {
        // Queue a job to sync the repository
        // For simplicity, we'll just sync directly here
        $this->gitService->syncRepository($repository);

        return response()->json(['status' => 'success', 'action' => 'repository_synced']);
    }

    /**
     * Verify GitHub webhook signature
     */
    protected function verifyGitHubSignature(Request $request, string $secret): bool
    {
        $signature = $request->header('X-Hub-Signature-256');

        if (!$signature) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = 'sha256='.hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}
