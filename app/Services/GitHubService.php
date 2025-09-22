<?php

namespace App\Services;

use App\Models\GitRepository;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GitHubService
{
    protected ?GitRepository $repository = null;

    protected string $baseUrl = 'https://api.github.com';

    protected string $authUrl = 'https://github.com/login/oauth/authorize';

    protected string $tokenUrl = 'https://github.com/login/oauth/access_token';

    public function __construct(?GitRepository $repository = null)
    {
        $this->repository = $repository;
    }

    /**
     * Get OAuth URL for GitHub SSO
     *
     * @param  string  $redirectUrl  URL to redirect after authentication
     * @return string URL to redirect user for SSO authentication
     */
    public function getOAuthUrl(string $redirectUrl): string
    {
        $clientId = config('services.github.client_id');

        if (!$clientId) {
            throw new Exception('GitHub client ID is not configured');
        }

        $state = Str::random(40);

        // Store state in session for verification
        session(['github_oauth_state' => $state]);

        $params = [
            'client_id' => $clientId,
            'redirect_uri' => $redirectUrl,
            'scope' => 'repo admin:repo_hook user',
            'state' => $state,
        ];

        return $this->authUrl.'?'.http_build_query($params);
    }

    /**
     * Exchange OAuth code for access token
     *
     * @param  array  $callbackData  Data from OAuth callback
     * @return array|null Token data or null on failure
     */
    public function exchangeCodeForToken(array $callbackData): ?array
    {
        // Verify state to prevent CSRF
        if (empty($callbackData['state']) || $callbackData['state'] !== session('github_oauth_state')) {
            Log::error('GitHub OAuth state mismatch');

            return null;
        }

        if (empty($callbackData['code'])) {
            Log::error('GitHub OAuth code missing');

            return null;
        }

        try {
            $response = Http::acceptJson()->post($this->tokenUrl, [
                'client_id' => config('services.github.client_id'),
                'client_secret' => config('services.github.client_secret'),
                'code' => $callbackData['code'],
            ]);

            if (!$response->successful()) {
                Log::error('GitHub OAuth token exchange failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();

            if (empty($data['access_token'])) {
                Log::error('GitHub OAuth token missing from response', [
                    'response' => $data,
                ]);

                return null;
            }

            return [
                'access_token' => $data['access_token'],
                'token_type' => $data['token_type'] ?? 'bearer',
                'scope' => $data['scope'] ?? '',
                // GitHub tokens don't expire by default
                'expires_in' => null,
                'refresh_token' => null,
            ];
        } catch (Exception $e) {
            Log::error('GitHub OAuth token exchange exception', [
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get repository information using access token
     *
     * @param  string  $accessToken  OAuth access token
     * @return array|null Repository information or null on failure
     */
    public function getRepositoryInfoFromToken(string $accessToken): ?array
    {
        try {
            // First get authenticated user
            $userResponse = Http::withToken($accessToken)
                ->withHeaders([
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'Fokus-App',
                ])
                ->get('https://api.github.com/user');

            if (!$userResponse->successful()) {
                Log::error('Failed to get GitHub user info', [
                    'status' => $userResponse->status(),
                    'response' => $userResponse->body(),
                ]);

                return null;
            }

            $user = $userResponse->json();

            // Get user repositories
            $reposResponse = Http::withToken($accessToken)
                ->withHeaders([
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'Fokus-App',
                ])
                ->get('https://api.github.com/user/repos', [
                    'sort' => 'updated',
                    'per_page' => 100,
                ]);

            if (!$reposResponse->successful() || empty($reposResponse->json())) {
                Log::error('Failed to get GitHub repositories', [
                    'status' => $reposResponse->status(),
                    'response' => $reposResponse->body(),
                ]);

                return null;
            }

            $repos = $reposResponse->json();
            $repo = $repos[0]; // Use the most recently updated repo

            return [
                'name' => $repo['name'],
                'url' => $repo['html_url'],
                'default_branch' => $repo['default_branch'],
                'owner' => $repo['owner']['login'],
                'description' => $repo['description'],
            ];
        } catch (Exception $e) {
            Log::error('Failed to get GitHub repository info', [
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get repository branches
     */
    public function getBranches(): array
    {
        $url = $this->getRepoApiUrl().'/branches';
        $response = $this->makeRequest('GET', $url);

        $branches = [];
        foreach ($response as $branch) {
            $branches[] = [
                'name' => $branch['name'],
                'status' => 'active',
                'last_commit_hash' => $branch['commit']['sha'] ?? null,
                'last_commit_date' => null, // GitHub API doesn't provide this directly
            ];
        }

        return $branches;
    }

    /**
     * Get repository commits
     */
    public function getCommits(int $limit = 100): array
    {
        $url = $this->getRepoApiUrl().'/commits';
        $response = $this->makeRequest('GET', $url, ['per_page' => $limit]);

        $commits = [];
        foreach ($response as $commit) {
            $commits[] = [
                'hash' => $commit['sha'],
                'message' => $commit['commit']['message'],
                'author_name' => $commit['commit']['author']['name'],
                'author_email' => $commit['commit']['author']['email'],
                'committed_date' => $commit['commit']['author']['date'],
                'branch' => null, // GitHub API doesn't provide this directly
            ];
        }

        return $commits;
    }

    /**
     * Get repository pull requests
     */
    public function getPullRequests(): array
    {
        $url = $this->getRepoApiUrl().'/pulls';
        $response = $this->makeRequest('GET', $url, ['state' => 'all']);

        $pullRequests = [];
        foreach ($response as $pr) {
            $status = 'open';
            if ($pr['merged_at']) {
                $status = 'merged';
            } elseif ($pr['closed_at']) {
                $status = 'closed';
            }

            $pullRequests[] = [
                'number' => $pr['number'],
                'title' => $pr['title'],
                'description' => $pr['body'],
                'status' => $status,
                'source_branch' => $pr['head']['ref'],
                'target_branch' => $pr['base']['ref'],
                'author_email' => $pr['user']['email'] ?? null,
                'merged_at' => $pr['merged_at'],
                'closed_at' => $pr['closed_at'],
                'url' => $pr['html_url'],
            ];
        }

        return $pullRequests;
    }

    /**
     * Get a specific pull request
     */
    public function getPullRequest(int $number): ?array
    {
        $url = $this->getRepoApiUrl().'/pulls/'.$number;
        $pr = $this->makeRequest('GET', $url);

        if (!$pr) {
            return null;
        }

        $status = 'open';
        if ($pr['merged_at']) {
            $status = 'merged';
        } elseif ($pr['closed_at']) {
            $status = 'closed';
        }

        return [
            'number' => $pr['number'],
            'title' => $pr['title'],
            'description' => $pr['body'],
            'status' => $status,
            'source_branch' => $pr['head']['ref'],
            'target_branch' => $pr['base']['ref'],
            'author_email' => $pr['user']['email'] ?? null,
            'merged_at' => $pr['merged_at'],
            'closed_at' => $pr['closed_at'],
            'url' => $pr['html_url'],
        ];
    }

    /**
     * Create a new branch
     */
    public function createBranch(string $branchName, string $baseBranch): bool
    {
        try {
            // First, get the SHA of the latest commit on the base branch
            $url = $this->getRepoApiUrl().'/git/refs/heads/'.$baseBranch;
            $baseRef = $this->makeRequest('GET', $url);

            if (!$baseRef || !isset($baseRef['object']['sha'])) {
                return false;
            }

            $sha = $baseRef['object']['sha'];

            // Create the new branch
            $url = $this->getRepoApiUrl().'/git/refs';
            $response = $this->makeRequest('POST', $url, [
                'ref' => 'refs/heads/'.$branchName,
                'sha' => $sha,
            ]);

            return isset($response['ref']);
        } catch (Exception $e) {
            Log::error('Failed to create branch on GitHub: '.$e->getMessage(), [
                'repository' => $this->repository->name,
                'branch' => $branchName,
                'exception' => $e,
            ]);

            return false;
        }
    }

    /**
     * Get file diff for a commit
     */
    public function getFileDiff(string $commitHash, string $filePath): ?string
    {
        $url = $this->getRepoApiUrl().'/commits/'.$commitHash;
        $response = $this->makeRequest('GET', $url);

        if (!$response || !isset($response['files'])) {
            return null;
        }

        foreach ($response['files'] as $file) {
            if ($file['filename'] === $filePath && isset($file['patch'])) {
                return $file['patch'];
            }
        }

        return null;
    }

    /**
     * Make an API request to GitHub
     */
    protected function makeRequest(string $method, string $url, array $data = []): ?array
    {
        try {
            // Check if token needs refresh
            if ($this->repository && $this->repository->api_token_expires_at && $this->repository->api_token_expires_at->isPast()) {
                app(GitService::class)->refreshTokenIfNeeded($this->repository);
            }

            $response = Http::withToken($this->repository->api_token)
                ->withHeaders([
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'Fokus-App',
                ])
                ->{strtolower($method)}($url, $method === 'GET' ? ['query' => $data] : $data);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('GitHub API error: '.$response->status(), [
                'url' => $url,
                'response' => $response->body(),
            ]);

            return null;
        } catch (Exception $e) {
            Log::error('GitHub API request failed: '.$e->getMessage(), [
                'url' => $url,
                'exception' => $e,
            ]);

            return null;
        }
    }

    /**
     * Get the API URL for the repository
     */
    protected function getRepoApiUrl(): string
    {
        if (!$this->repository) {
            throw new Exception('Repository not set');
        }

        // Extract owner and repo from repository URL
        $repoUrl = $this->repository->repository_url;
        $parts = explode('/', parse_url($repoUrl, PHP_URL_PATH));
        $parts = array_values(array_filter($parts));

        if (count($parts) < 2) {
            throw new Exception("Invalid GitHub repository URL: {$repoUrl}");
        }

        $owner = $parts[count($parts) - 2];
        $repo = $parts[count($parts) - 1];

        // Remove .git suffix if present
        $repo = preg_replace('/\.git$/', '', $repo);

        return "{$this->baseUrl}/repos/{$owner}/{$repo}";
    }
}
