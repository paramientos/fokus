<?php

namespace App\Services;

use App\Models\GitBranch;
use App\Models\GitCommit;
use App\Models\GitPullRequest;
use App\Models\GitRepository;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GitService
{
    /**
     * Provider specific services
     */
    protected array $providers = [
        'github' => GitHubService::class,
        'gitlab' => GitLabService::class,
        'bitbucket' => BitbucketService::class,
    ];

    /**
     * Get the appropriate provider service for a repository
     */
    public function getProviderService(GitRepository $repository)
    {
        if (!isset($this->providers[$repository->provider])) {
            throw new Exception("Unsupported Git provider: {$repository->provider}");
        }

        $providerClass = $this->providers[$repository->provider];
        return new $providerClass($repository);
    }

    /**
     * Connect to a Git provider using SSO
     *
     * @param string $provider Provider name (github, gitlab, bitbucket)
     * @param string $redirectUrl URL to redirect after authentication
     * @return string URL to redirect user for SSO authentication
     * @throws Exception
     */
    public function connectWithSSO(string $provider, string $redirectUrl): string
    {
        if (!isset($this->providers[$provider])) {
            throw new Exception("Unsupported Git provider: {$provider}");
        }

        $providerClass = $this->providers[$provider];

        /** @var GitHubService|GitLabService|BitbucketService $providerInstance */
        $providerInstance = new $providerClass();

        return $providerInstance->getOAuthUrl($redirectUrl);
    }

    /**
     * @throws Exception
     */
    public function handleSSOCallback(string $provider, array $callbackData, int $projectId): ?GitRepository
    {
        if (!isset($this->providers[$provider])) {
            throw new Exception("Unsupported Git provider: {$provider}");
        }

        $providerClass = $this->providers[$provider];

        /** @var GitHubService|GitLabService|BitbucketService $providerInstance */
        $providerInstance = new $providerClass();

        // Exchange code for token
        $tokenData = $providerInstance->exchangeCodeForToken($callbackData);

        if (!$tokenData || empty($tokenData['access_token'])) {
            return null;
        }

        // Get repository information
        $repoInfo = $providerInstance->getRepositoryInfoFromToken($tokenData['access_token']);

        if (!$repoInfo) {
            return null;
        }

        // Get project to retrieve workspace_id
        $project = Project::findOrFail($projectId);

        if (!$project) {
            return null;
        }

        // Create or update repository
        return GitRepository::updateOrCreate(
            [
                'project_id' => $projectId,
                'provider' => $provider,
                'repository_url' => $repoInfo['url'],
            ],
            [
                'workspace_id' => $project->workspace_id,
                'name' => $repoInfo['name'],
                'api_token' => $tokenData['access_token'],
                'api_token_expires_at' => isset($tokenData['expires_in']) ? now()->addSeconds($tokenData['expires_in']) : null,
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'default_branch' => $repoInfo['default_branch'] ?? 'main',
                'webhook_secret' => Str::random(32),
            ]
        );
    }

    /**
     * Refresh token for a repository if needed
     *
     * @param GitRepository $repository Repository to refresh token for
     * @return bool True if token was refreshed successfully or didn't need refreshing
     */
    public function refreshTokenIfNeeded(GitRepository $repository): bool
    {
        // If token doesn't expire or expiry is in the future, no need to refresh
        if (!$repository->api_token_expires_at || $repository->api_token_expires_at->isFuture()) {
            return true;
        }

        // If no refresh token, can't refresh
        if (!$repository->refresh_token) {
            return false;
        }

        $provider = $this->getProviderService($repository);

        if (!method_exists($provider, 'refreshToken')) {
            return false;
        }

        $tokenData = $provider->refreshToken($repository->refresh_token);

        if (!$tokenData || empty($tokenData['access_token'])) {
            return false;
        }

        $repository->update([
            'api_token' => $tokenData['access_token'],
            'api_token_expires_at' => isset($tokenData['expires_in']) ? now()->addSeconds($tokenData['expires_in']) : null,
            'refresh_token' => $tokenData['refresh_token'] ?? $repository->refresh_token,
        ]);

        return true;
    }

    /**
     * Sync a repository with its remote source
     */
    public function syncRepository(GitRepository $repository): bool
    {
        try {
            $provider = $this->getProviderService($repository);

            // Sync branches
            $branches = $provider->getBranches();
            $this->syncBranches($repository, $branches);

            // Sync commits (limit to last 100 for performance)
            $commits = $provider->getCommits(100);
            $this->syncCommits($repository, $commits);

            // Sync pull requests
            $pullRequests = $provider->getPullRequests();
            $this->syncPullRequests($repository, $pullRequests);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to sync repository: ' . $e->getMessage(), [
                'repository' => $repository->name,
                'exception' => $e,
            ]);
            return false;
        }
    }

    /**
     * Create a new branch for a task
     */
    public function createBranchForTask(Task $task, GitRepository $repository, string $branchName = null): ?GitBranch
    {
        try {
            $provider = $this->getProviderService($repository);

            // Generate branch name if not provided
            if (!$branchName) {
                $prefix = $repository->branch_prefix ?? 'feature';
                $taskId = $task->id;
                $slug = Str::slug(Str::limit($task->title, 30));
                $branchName = "{$prefix}/{$taskId}-{$slug}";
            }

            // Create branch on remote
            $result = $provider->createBranch($branchName, $repository->default_branch);

            if ($result) {
                // Create branch record in database
                return GitBranch::create([
                    'repository_id' => $repository->id,
                    'task_id' => $task->id,
                    'name' => $branchName,
                    'status' => 'active',
                    'created_by' => auth()->id(),
                ]);
            }

            return null;
        } catch (Exception $e) {
            Log::error('Failed to create branch: ' . $e->getMessage(), [
                'task' => $task->id,
                'repository' => $repository->name,
                'exception' => $e,
            ]);
            return null;
        }
    }

    /**
     * Get commits for a task
     */
    public function getCommitsForTask(Task $task)
    {
        return GitCommit::where('task_id', $task->id)
            ->orderBy('committed_date', 'desc')
            ->get();
    }

    /**
     * Get pull requests for a task
     */
    public function getPullRequestsForTask(Task $task)
    {
        return GitPullRequest::where('task_id', $task->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Sync pull request status
     */
    public function syncPullRequestStatus(GitPullRequest $pr): bool
    {
        try {
            $repository = $pr->repository;
            $provider = $this->getProviderService($repository);

            $prData = $provider->getPullRequest($pr->number);

            if ($prData) {
                $pr->update([
                    'status' => $prData['status'],
                    'merged_at' => $prData['merged_at'],
                    'closed_at' => $prData['closed_at'],
                ]);

                // If PR is merged or closed, update task status if configured
                if (($pr->status === 'merged' || $pr->status === 'closed') && $pr->task) {
                    $this->updateTaskStatusFromPR($pr);
                }

                return true;
            }

            return false;
        } catch (Exception $e) {
            Log::error('Failed to sync PR status: ' . $e->getMessage(), [
                'pull_request' => $pr->id,
                'exception' => $e,
            ]);
            return false;
        }
    }

    /**
     * Get file diff for a commit
     */
    public function getFileDiff(GitCommit $commit, string $filePath): ?string
    {
        try {
            $repository = $commit->repository;
            $provider = $this->getProviderService($repository);

            return $provider->getFileDiff($commit->hash, $filePath);
        } catch (Exception $e) {
            Log::error('Failed to get file diff: ' . $e->getMessage(), [
                'commit' => $commit->id,
                'file' => $filePath,
                'exception' => $e,
            ]);
            return null;
        }
    }

    /**
     * Extract task ID from commit message
     */
    public function extractTaskIdFromCommit(string $message): ?int
    {
        // Match patterns like #123, TASK-123, or task #123
        if (preg_match('/#(\d+)|[Tt][Aa][Ss][Kk][-\s#]?(\d+)/', $message, $matches)) {
            return intval($matches[1] ?? $matches[2]);
        }

        return null;
    }

    /**
     * Sync branches from remote
     */
    protected function syncBranches(GitRepository $repository, array $branches): void
    {
        foreach ($branches as $branch) {
            GitBranch::updateOrCreate(
                [
                    'repository_id' => $repository->id,
                    'name' => $branch['name'],
                ],
                [
                    'status' => $branch['status'] ?? 'active',
                    'last_commit_hash' => $branch['last_commit_hash'] ?? null,
                    'last_commit_date' => $branch['last_commit_date'] ?? null,
                ]
            );
        }
    }

    /**
     * Sync commits from remote
     */
    protected function syncCommits(GitRepository $repository, array $commits): void
    {
        foreach ($commits as $commit) {
            // Try to extract task ID from commit message
            $taskId = $this->extractTaskIdFromCommit($commit['message']);

            // Find branch
            $branch = null;
            if (!empty($commit['branch'])) {
                $branch = GitBranch::where('repository_id', $repository->id)
                    ->where('name', $commit['branch'])
                    ->first();
            }

            GitCommit::updateOrCreate(
                [
                    'repository_id' => $repository->id,
                    'hash' => $commit['hash'],
                ],
                [
                    'branch_id' => $branch?->id,
                    'task_id' => $taskId,
                    'message' => $commit['message'],
                    'author_name' => $commit['author_name'],
                    'author_email' => $commit['author_email'],
                    'committed_date' => $commit['committed_date'],
                    'files_changed' => $commit['files_changed'] ?? null,
                    'additions' => $commit['additions'] ?? 0,
                    'deletions' => $commit['deletions'] ?? 0,
                ]
            );
        }
    }

    /**
     * Sync pull requests from remote
     */
    protected function syncPullRequests(GitRepository $repository, array $pullRequests): void
    {
        foreach ($pullRequests as $pr) {
            // Try to extract task ID from PR title or description
            $taskId = $this->extractTaskIdFromCommit($pr['title'] . ' ' . ($pr['description'] ?? ''));

            // Find author
            $author = null;
            if (!empty($pr['author_email'])) {
                $author = User::where('email', $pr['author_email'])->first();
            }

            GitPullRequest::updateOrCreate(
                [
                    'repository_id' => $repository->id,
                    'number' => $pr['number'],
                ],
                [
                    'task_id' => $taskId,
                    'title' => $pr['title'],
                    'description' => $pr['description'] ?? null,
                    'status' => $pr['status'],
                    'source_branch' => $pr['source_branch'],
                    'target_branch' => $pr['target_branch'],
                    'author_id' => $author?->id,
                    'merged_at' => $pr['merged_at'] ?? null,
                    'closed_at' => $pr['closed_at'] ?? null,
                    'url' => $pr['url'],
                ]
            );
        }
    }

    /**
     * Update task status based on PR status
     */
    protected function updateTaskStatusFromPR(GitPullRequest $pr): void
    {
        $task = $pr->task;
        if (!$task) {
            return;
        }

        // This logic can be customized based on your workflow
        if ($pr->status === 'merged') {
            // Find a status like "Done" or "Ready for QA"
            $doneStatus = $task->project->statuses()
                ->where('name', 'like', '%Done%')
                ->orWhere('name', 'like', '%Ready for QA%')
                ->first();

            if ($doneStatus) {
                $task->update(['status_id' => $doneStatus->id]);
            }
        }
    }
}
