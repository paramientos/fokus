<?php

namespace App\Services;

use App\Models\GitRepository;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BitbucketService
{
    protected GitRepository $repository;

    protected string $baseUrl = 'https://api.bitbucket.org/2.0';

    public function __construct(GitRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get repository branches
     */
    public function getBranches(): array
    {
        $url = $this->getRepoApiUrl().'/refs/branches';
        $response = $this->makeRequest('GET', $url);

        $branches = [];
        if (isset($response['values'])) {
            foreach ($response['values'] as $branch) {
                $branches[] = [
                    'name' => $branch['name'],
                    'status' => 'active',
                    'last_commit_hash' => $branch['target']['hash'] ?? null,
                    'last_commit_date' => $branch['target']['date'] ?? null,
                ];
            }
        }

        return $branches;
    }

    /**
     * Get repository commits
     */
    public function getCommits(int $limit = 100): array
    {
        $url = $this->getRepoApiUrl().'/commits';
        $response = $this->makeRequest('GET', $url, ['limit' => $limit]);

        $commits = [];
        if (isset($response['values'])) {
            foreach ($response['values'] as $commit) {
                $commits[] = [
                    'hash' => $commit['hash'],
                    'message' => $commit['message'],
                    'author_name' => $commit['author']['user']['display_name'] ?? $commit['author']['raw'],
                    'author_email' => $commit['author']['raw'] ?? null,
                    'committed_date' => $commit['date'],
                    'branch' => null, // Bitbucket API doesn't provide this directly
                ];
            }
        }

        return $commits;
    }

    /**
     * Get repository pull requests
     */
    public function getPullRequests(): array
    {
        $url = $this->getRepoApiUrl().'/pullrequests';
        $openPRs = $this->makeRequest('GET', $url, ['state' => 'OPEN']);
        $mergedPRs = $this->makeRequest('GET', $url, ['state' => 'MERGED']);
        $declinedPRs = $this->makeRequest('GET', $url, ['state' => 'DECLINED']);

        $pullRequests = [];

        // Process open PRs
        if (isset($openPRs['values'])) {
            foreach ($openPRs['values'] as $pr) {
                $pullRequests[] = $this->formatPullRequest($pr, 'open');
            }
        }

        // Process merged PRs
        if (isset($mergedPRs['values'])) {
            foreach ($mergedPRs['values'] as $pr) {
                $pullRequests[] = $this->formatPullRequest($pr, 'merged');
            }
        }

        // Process declined PRs
        if (isset($declinedPRs['values'])) {
            foreach ($declinedPRs['values'] as $pr) {
                $pullRequests[] = $this->formatPullRequest($pr, 'closed');
            }
        }

        return $pullRequests;
    }

    /**
     * Get a specific pull request
     */
    public function getPullRequest(int $number): ?array
    {
        $url = $this->getRepoApiUrl().'/pullrequests/'.$number;
        $pr = $this->makeRequest('GET', $url);

        if (!$pr) {
            return null;
        }

        $status = 'open';
        if ($pr['state'] === 'MERGED') {
            $status = 'merged';
        } elseif ($pr['state'] === 'DECLINED') {
            $status = 'closed';
        }

        return $this->formatPullRequest($pr, $status);
    }

    /**
     * Create a new branch
     */
    public function createBranch(string $branchName, string $baseBranch): bool
    {
        try {
            // First, get the hash of the latest commit on the base branch
            $url = $this->getRepoApiUrl().'/refs/branches/'.$baseBranch;
            $baseRef = $this->makeRequest('GET', $url);

            if (!$baseRef || !isset($baseRef['target']['hash'])) {
                return false;
            }

            $hash = $baseRef['target']['hash'];

            // Create the new branch
            $url = $this->getRepoApiUrl().'/refs/branches';
            $response = $this->makeRequest('POST', $url, [
                'name' => $branchName,
                'target' => [
                    'hash' => $hash,
                ],
            ]);

            return isset($response['name']);
        } catch (Exception $e) {
            Log::error('Failed to create branch on Bitbucket: '.$e->getMessage(), [
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
        $url = $this->getRepoApiUrl().'/diffstat/'.$commitHash;
        $response = $this->makeRequest('GET', $url);

        if (!$response || !isset($response['values'])) {
            return null;
        }

        foreach ($response['values'] as $file) {
            if ($file['new']['path'] === $filePath) {
                // Get the specific file diff
                $diffUrl = $this->getRepoApiUrl().'/diff/'.$commitHash.'/'.$filePath;
                $diffResponse = $this->makeRequest('GET', $diffUrl);

                if ($diffResponse) {
                    return $diffResponse;
                }
            }
        }

        return null;
    }

    /**
     * Format a pull request from Bitbucket API response
     */
    protected function formatPullRequest(array $pr, string $status): array
    {
        $mergedAt = null;
        $closedAt = null;

        if ($status === 'merged' || $status === 'closed') {
            // Bitbucket doesn't provide exact merged_at or closed_at timestamps
            // Use updated_on as an approximation
            if (isset($pr['updated_on'])) {
                if ($status === 'merged') {
                    $mergedAt = $pr['updated_on'];
                } else {
                    $closedAt = $pr['updated_on'];
                }
            }
        }

        return [
            'number' => $pr['id'],
            'title' => $pr['title'],
            'description' => $pr['description'],
            'status' => $status,
            'source_branch' => $pr['source']['branch']['name'],
            'target_branch' => $pr['destination']['branch']['name'],
            'author_email' => null, // Bitbucket API doesn't provide this directly
            'merged_at' => $mergedAt,
            'closed_at' => $closedAt,
            'url' => $pr['links']['html']['href'],
        ];
    }

    /**
     * Make an API request to Bitbucket
     */
    protected function makeRequest(string $method, string $url, array $data = []): ?array
    {
        try {
            // Bitbucket uses Basic Auth with app password
            // The token should be stored as "username:app_password"
            [$username, $appPassword] = explode(':', $this->repository->api_token);

            $response = Http::withBasicAuth($username, $appPassword)
                ->withHeaders([
                    'User-Agent' => 'Fokus-App',
                ])
                ->{strtolower($method)}($url, $method === 'GET' ? ['query' => $data] : $data);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Bitbucket API error: '.$response->status(), [
                'url' => $url,
                'response' => $response->body(),
            ]);

            return null;
        } catch (Exception $e) {
            Log::error('Bitbucket API request failed: '.$e->getMessage(), [
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
        // Extract workspace and repo slug from repository URL
        $repoUrl = $this->repository->repository_url;
        $parts = explode('/', parse_url($repoUrl, PHP_URL_PATH));
        $parts = array_values(array_filter($parts));

        if (count($parts) < 2) {
            throw new Exception("Invalid Bitbucket repository URL: {$repoUrl}");
        }

        $workspace = $parts[0];
        $repoSlug = $parts[1];

        // Remove .git suffix if present
        $repoSlug = preg_replace('/\.git$/', '', $repoSlug);

        return "{$this->baseUrl}/repositories/{$workspace}/{$repoSlug}";
    }
}
