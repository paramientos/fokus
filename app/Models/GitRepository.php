<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 *
 *
 * @property int $id
 * @property int $workspace_id
 * @property int $project_id
 * @property string $name
 * @property string $provider
 * @property string $repository_url
 * @property string|null $api_token
 * @property \Illuminate\Support\Carbon|null $api_token_expires_at
 * @property string|null $refresh_token
 * @property string|null $webhook_secret
 * @property string $default_branch
 * @property string|null $branch_prefix
 * @property array<array-key, mixed>|null $settings
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GitBranch> $branches
 * @property-read int|null $branches_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GitCommit> $commits
 * @property-read int|null $commits_count
 * @property-read \App\Models\Project $project
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GitPullRequest> $pullRequests
 * @property-read int|null $pull_requests_count
 * @property-read \App\Models\Workspace $workspace
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitRepository newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitRepository newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitRepository query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitRepository whereApiToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitRepository whereApiTokenExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitRepository whereBranchPrefix($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitRepository whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitRepository whereDefaultBranch($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitRepository whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitRepository whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitRepository whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitRepository whereProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitRepository whereRefreshToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitRepository whereRepositoryUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitRepository whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitRepository whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitRepository whereWebhookSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitRepository whereWorkspaceId($value)
 * @mixin \Eloquent
 */
class GitRepository extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = [
        'workspace_id',
        'project_id',
        'name',
        'provider', // github, gitlab, bitbucket
        'repository_url',
        'api_token',
        'api_token_expires_at',
        'refresh_token',
        'webhook_secret',
        'default_branch',
        'branch_prefix',
        'settings',
    ];

    protected $casts = [
        'settings' => 'json',
        'api_token_expires_at' => 'datetime',
    ];

    /**
     * Get the project that owns the repository.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the workspace that owns the repository.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the branches for the repository.
     */
    public function branches(): HasMany
    {
        return $this->hasMany(GitBranch::class);
    }

    /**
     * Get the pull requests for the repository.
     */
    public function pullRequests(): HasMany
    {
        return $this->hasMany(GitPullRequest::class);
    }

    /**
     * Get the commits for the repository.
     */
    public function commits(): HasMany
    {
        return $this->hasMany(GitCommit::class);
    }

    /**
     * Check if the repository is from GitHub.
     */
    public function isGithub(): bool
    {
        return $this->provider === 'github';
    }

    /**
     * Check if the repository is from GitLab.
     */
    public function isGitlab(): bool
    {
        return $this->provider === 'gitlab';
    }

    /**
     * Check if the repository is from Bitbucket.
     */
    public function isBitbucket(): bool
    {
        return $this->provider === 'bitbucket';
    }
}
