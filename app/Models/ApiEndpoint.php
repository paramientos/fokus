<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * API Endpoint modeli
 *
 * @property string $id
 * @property string $name
 * @property string $url
 * @property string $method
 * @property string|null $description
 * @property array<array-key, mixed>|null $headers
 * @property array<array-key, mixed>|null $params
 * @property array<array-key, mixed>|null $body
 * @property string|null $task_id
 * @property string|null $project_id
 * @property string $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ApiEndpointHistory> $history
 * @property-read int|null $history_count
 * @property-read \App\Models\Project|null $project
 * @property-read \App\Models\Task|null $task
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiEndpoint newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiEndpoint newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiEndpoint query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiEndpoint whereBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiEndpoint whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiEndpoint whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiEndpoint whereHeaders($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiEndpoint whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiEndpoint whereMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiEndpoint whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiEndpoint whereParams($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiEndpoint whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiEndpoint whereTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiEndpoint whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiEndpoint whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiEndpoint whereUserId($value)
 * @mixin \Eloquent
 */
class ApiEndpoint extends Model
{
    use HasFactory,HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'url',
        'method',
        'description',
        'headers',
        'params',
        'body',
        'task_id',
        'project_id',
        'user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'headers' => 'array',
        'params' => 'array',
        'body' => 'array',
    ];

    /**
     * API endpoint'in bağlı olduğu görev
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * API endpoint'in bağlı olduğu proje
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * API endpoint'i oluşturan kullanıcı
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * API endpoint'in geçmiş çalıştırma kayıtları
     */
    public function history(): HasMany
    {
        return $this->hasMany(ApiEndpointHistory::class);
    }
}
