<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * API Endpoint çalıştırma geçmişi modeli
 *
 * @property string $id
 * @property string $api_endpoint_id
 * @property string $request_url
 * @property string $request_method
 * @property array<array-key, mixed>|null $request_headers
 * @property array<array-key, mixed>|null $request_body
 * @property int $response_status_code
 * @property array<array-key, mixed>|null $response_headers
 * @property array<array-key, mixed>|null $response_body
 * @property int $execution_time_ms
 * @property string $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ApiEndpoint $apiEndpoint
 * @property-read \App\Models\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiEndpointHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiEndpointHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiEndpointHistory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiEndpointHistory whereApiEndpointId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiEndpointHistory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiEndpointHistory whereExecutionTimeMs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiEndpointHistory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiEndpointHistory whereRequestBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiEndpointHistory whereRequestHeaders($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiEndpointHistory whereRequestMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiEndpointHistory whereRequestUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiEndpointHistory whereResponseBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiEndpointHistory whereResponseHeaders($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiEndpointHistory whereResponseStatusCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiEndpointHistory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiEndpointHistory whereUserId($value)
 *
 * @mixin \Eloquent
 */
class ApiEndpointHistory extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'api_endpoint_id',
        'request_url',
        'request_method',
        'request_headers',
        'request_body',
        'response_status_code',
        'response_headers',
        'response_body',
        'execution_time_ms',
        'user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'request_headers' => 'array',
        'request_body' => 'array',
        'response_headers' => 'array',
        'response_body' => 'array',
    ];

    /**
     * Bu geçmiş kaydının bağlı olduğu API endpoint
     */
    public function apiEndpoint(): BelongsTo
    {
        return $this->belongsTo(ApiEndpoint::class);
    }

    /**
     * Bu geçmiş kaydını oluşturan kullanıcı
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
