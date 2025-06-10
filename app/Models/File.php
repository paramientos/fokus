<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 
 *
 * @property int $id
 * @property string $file_name
 * @property string $file_path
 * @property string $mime_type
 * @property int $size
 * @property int $uploaded_by
 * @property string $fileable_type
 * @property int $fileable_id
 * @property int|null $parent_id
 * @property int $version
 * @property int $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\FileComment> $comments
 * @property-read int|null $comments_count
 * @property-read Model|\Eloquent $fileable
 * @property-read File|null $parent
 * @property-read \App\Models\User $uploader
 * @property-read \Illuminate\Database\Eloquent\Collection<int, File> $versions
 * @property-read int|null $versions_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereFileName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereFileableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereFileableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereMimeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereUploadedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File withoutTrashed()
 * @mixin \Eloquent
 */
class File extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'file_name',
        'file_path',
        'mime_type',
        'size',
        'uploaded_by',
        'fileable_id',
        'fileable_type',
        'parent_id',
        'version',
        'is_active',
    ];

    public function fileable()
    {
        return $this->morphTo();
    }

    public function parent()
    {
        return $this->belongsTo(File::class, 'parent_id');
    }

    public function versions()
    {
        return $this->hasMany(File::class, 'parent_id');
    }

    public function comments()
    {
        return $this->hasMany(FileComment::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
