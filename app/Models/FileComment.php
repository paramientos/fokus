<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $file_id
 * @property string $user_id
 * @property string $comment
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\File $file
 * @property-read \App\Models\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileComment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileComment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileComment onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileComment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileComment whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileComment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileComment whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileComment whereFileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileComment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileComment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileComment whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileComment withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileComment withoutTrashed()
 *
 * @mixin \Eloquent
 */
class FileComment extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'file_id',
        'user_id',
        'comment',
    ];

    public function file()
    {
        return $this->belongsTo(File::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
