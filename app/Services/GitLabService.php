<?php

namespace App\Services;

use App\Models\GitRepository;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GitLabService
{
    protected ?GitRepository $repository = null;

    protected string $baseUrl = 'https://gitlab.com/api/v4';

    protected string $authUrl = 'https://gitlab.com/oauth/authorize';

    protected string $tokenUrl = 'https://gitlab.com/oauth/token';

    public function __construct(?GitRepository $repository = null)
    {
        $this->repository = $repository;
    }

    /**
     * Get OAuth URL for GitLab SSO
     *
     * @param  string  $redirectUrl  URL to redirect after authentication
     * @return string URL to redirect user for SSO authentication
     */
    public function getOAuthUrl(string $redirectUrl): string
    {
        $clientId = config('services.gitlab.client_id');

        if (!$clientId) {
            throw new Exception('GitLab client ID is not configured');
        }

        $state = Str::random(40);

        // Store state in session for verification
        session(['gitlab_oauth_state' => $state]);

        $params = [
            'client_id' => $clientId,
            'redirect_uri' => $redirectUrl,
            'response_type' => 'code',
            'scope' => 'api read_repository write_repository',
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
        if (empty($callbackData['state']) || $callbackData['state'] !== session('gitlab_oauth_state')) {
            Log::error('GitLab OAuth state mismatch');

            return null;
        }

        if (empty($callbackData['code'])) {
            Log::error('GitLab OAuth code missing');

            return null;
        }

        try {
            $response = Http::asForm()->post($this->tokenUrl, [
                'client_id' => config('services.gitlab.client_id'),
                'client_secret' => config('services.gitlab.client_secret'),
                'code' => $callbackData['code'],
                'grant_type' => 'authorization_code',
                'redirect_uri' => $callbackData['redirect_uri'] ?? url('/git/callback/gitlab'),
            ]);

            if (!$response->successful()) {
                Log::error('GitLab OAuth token exchange failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();

            if (empty($data['access_token'])) {
                Log::error('GitLab OAuth token missing from response', [
                    'response' => $data,
                ]);

                return null;
            }

            return [
                'access_token' => $data['access_token'],
                'token_type' => $data['token_type'] ?? 'bearer',
                'scope' => $data['scope'] ?? '',
                'expires_in' => $data['expires_in'] ?? null,
                'refresh_token' => $data['refresh_token'] ?? null,
                'created_at' => $data['created_at'] ?? now()->timestamp,
            ];
        } catch (Exception $e) {
            Log::error('GitLab OAuth token exchange exception', [
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Refresh an expired token
     *
     * @param  string  $refreshToken  Refresh token
     * @return array|null New token data or null on failure
     */
    public function refreshToken(string $refreshToken): ?array
    {
        try {
            $response = Http::asForm()->post($this->tokenUrl, [
                'client_id' => config('services.gitlab.client_id'),
                'client_secret' => config('services.gitlab.client_secret'),
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ]);

            if (!$response->successful()) {
                Log::error('GitLab OAuth token refresh failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();

            if (empty($data['access_token'])) {
                Log::error('GitLab OAuth refresh token missing from response', [
                    'response' => $data,
                ]);

                return null;
            }

            return [
                'access_token' => $data['access_token'],
                'token_type' => $data['token_type'] ?? 'bearer',
                'scope' => $data['scope'] ?? '',
                'expires_in' => $data['expires_in'] ?? null,
                'refresh_token' => $data['refresh_token'] ?? $refreshToken,
                'created_at' => $data['created_at'] ?? now()->timestamp,
            ];
        } catch (Exception $e) {
            Log::error('GitLab OAuth token refresh exception', [
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
            // Get user repositories
            $reposResponse = Http::withToken($accessToken)
                ->withHeaders([
                    'User-Agent' => 'Fokus-App',
                ])
                ->get($this->baseUrl.'/projects', [
                    'membership' => true,
                    'order_by' => 'last_activity_at',
                    'per_page' => 20,
                ]);

            if (!$reposResponse->successful() || empty($reposResponse->json())) {
                Log::error('Failed to get GitLab repositories', [
                    'status' => $reposResponse->status(),
                    'response' => $reposResponse->body(),
                ]);

                return null;
            }

            $repos = $reposResponse->json();
            $repo = $repos[0]; // Use the most recently active repo

            return [
                'name' => $repo['name'],
                'url' => $repo['web_url'],
                'default_branch' => $repo['default_branch'],
                'owner' => $repo['namespace']['path'],
                'description' => $repo['description'],
            ];
        } catch (Exception $e) {
            Log::error('Failed to get GitLab repository info', [
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
        $url = $this->getRepoApiUrl().'/repository/branches';
        $response = $this->makeRequest('GET', $url);

        $branches = [];
        foreach ($response as $branch) {
            $branches[] = [
                'name' => $branch['name'],
                'status' => 'active',
                'last_commit_hash' => $branch['commit']['id'] ?? null,
                'last_commit_date' => $branch['commit']['committed_date'] ?? null,
            ];
        }

        return $branches;
    }

    /**
     * Get repository commits
     */
    public function getCommits(int $limit = 100): array
    {
        $url = $this->getRepoApiUrl().'/repository/commits';
        $response = $this->makeRequest('GET', $url, ['per_page' => $limit]);

        $commits = [];
        foreach ($response as $commit) {
            $commits[] = [
                'hash' => $commit['id'],
                'message' => $commit['message'],
                'author_name' => $commit['author_name'],
                'author_email' => $commit['author_email'],
                'committed_date' => $commit['committed_date'],
                'branch' => null, // GitLab API doesn't provide this directly in commit list
            ];
        }

        return $commits;
    }

    /**
     * Get repository pull requests (merge requests in GitLab)
     */
    public function getPullRequests(): array
    {
        $url = $this->getRepoApiUrl().'/merge_requests';
        $response = $this->makeRequest('GET', $url, ['state' => 'all']);

        $pullRequests = [];
        foreach ($response as $mr) {
            $status = 'open';
            if ($mr['state'] === 'merged') {
                $status = 'merged';
            } elseif ($mr['state'] === 'closed') {
                $status = 'closed';
            }

            $pullRequests[] = [
                'number' => $mr['iid'],
                'title' => $mr['title'],
                'description' => $mr['description'],
                'status' => $status,
                'source_branch' => $mr['source_branch'],
                'target_branch' => $mr['target_branch'],
                'author_email' => null, // GitLab API doesn't provide this directly
                'merged_at' => $mr['merged_at'],
                'closed_at' => $mr['closed_at'],
                'url' => $mr['web_url'],
            ];
        }

        return $pullRequests;
    }

    /**
     * Get a specific pull request (merge request in GitLab)
     */
    public function getPullRequest(int $number): ?array
    {
        $url = $this->getRepoApiUrl().'/merge_requests/'.$number;
        $mr = $this->makeRequest('GET', $url);

        if (!$mr) {
            return null;
        }

        $status = 'open';
        if ($mr['state'] === 'merged') {
            $status = 'merged';
        } elseif ($mr['state'] === 'closed') {
            $status = 'closed';
        }

        return [
            'number' => $mr['iid'],
            'title' => $mr['title'],
            'description' => $mr['description'],
            'status' => $status,
            'source_branch' => $mr['source_branch'],
            'target_branch' => $mr['target_branch'],
            'author_email' => null, // GitLab API doesn't provide this directly
            'merged_at' => $mr['merged_at'],
            'closed_at' => $mr['closed_at'],
            'url' => $mr['web_url'],
        ];
    }

    /**
     * Create a new branch
     */
    public function createBranch(string $branchName, string $baseBranch): bool
    {
        try {
            $url = $this->getRepoApiUrl().'/repository/branches';
            $response = $this->makeRequest('POST', $url, [
                'branch' => $branchName,
                'ref' => $baseBranch,
            ]);

            return isset($response['name']);
        } catch (Exception $e) {
            Log::error('Failed to create branch on GitLab: '.$e->getMessage(), [
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
        $url = $this->getRepoApiUrl().'/repository/commits/'.$commitHash.'/diff';
        $response = $this->makeRequest('GET', $url);

        if (!$response) {
            return null;
        }

        foreach ($response as $file) {
            if ($file['new_path'] === $filePath && isset($file['diff'])) {
                return $file['diff'];
            }
        }

        return null;
    }

    /**
     * Make an API request to GitLab
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
                    'User-Agent' => 'Fokus-App',
                ])
                ->{strtolower($method)}($url, $method === 'GET' ? ['query' => $data] : $data);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('GitLab API error: '.$response->status(), [
                'url' => $url,
                'response' => $response->body(),
            ]);

            return null;
        } catch (Exception $e) {
            Log::error('GitLab API request failed: '.$e->getMessage(), [
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

        // Extract project ID or path from repository URL
        $repoUrl = $this->repository->repository_url;

        // Try to extract project path
        $parts = explode('/', parse_url($repoUrl, PHP_URL_PATH));
        $parts = array_values(array_filter($parts));

        if (count($parts) < 2) {
            throw new Exception("Invalid GitLab repository URL: {$repoUrl}");
        }

        // For GitLab, we need at least namespace/project
        $projectPath = implode('/', array_slice($parts, -2));

        // Remove .git suffix if present
        $projectPath = preg_replace('/\.git$/', '', $projectPath);

        // URL encode the path
        $projectPath = urlencode($projectPath);

        return "{$this->baseUrl}/projects/{$projectPath}";
    }
}
