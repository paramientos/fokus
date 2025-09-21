<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * 
 *
 * @property string $id
 * @property string $file_name
 * @property string $file_path
 * @property string $mime_type
 * @property int $size
 * @property string $uploaded_by
 * @property string $fileable_type
 * @property string $fileable_id
 * @property int $version
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string $parent_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\FileComment> $comments
 * @property-read int|null $comments_count
 * @property-read Model|\Eloquent $fileable
 * @property-read File $parent
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
    use SoftDeletes,HasUuids;

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

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // Dosya oluşturulduğunda workspace depolama alanını güncelle
        static::created(function (File $file) {
            $file->updateWorkspaceStorageUsage($file->size);
        });

        // Dosya silindiğinde workspace depolama alanını güncelle
        static::deleted(function (File $file) {
            $file->updateWorkspaceStorageUsage(-$file->size);

            // Fiziksel dosyayı da sil
            if (Storage::disk('public')->exists($file->file_path)) {
                Storage::disk('public')->delete($file->file_path);
            }
        });
    }

    /**
     * Workspace depolama alanını güncelle
     *
     * @param int $bytes Eklenecek veya çıkarılacak byte miktarı (negatif değer çıkarma işlemi yapar)
     * @return bool
     */
    public function updateWorkspaceStorageUsage(int $bytes): bool
    {
        // Dosyanın bağlı olduğu modeli al
        $fileable = $this->fileable;

        // Eğer fileable bir Project veya Task ise, workspace'i bul
        $workspace = null;

        if ($fileable instanceof Project) {
            $workspace = $fileable->workspace;
        } elseif ($fileable instanceof Task) {
            $workspace = $fileable->project->workspace;
        }

        if ($workspace) {
            $storageUsage = $workspace->getStorageUsage();

            if ($bytes > 0) {
                return $storageUsage->addUsage($bytes);
            } else {
                return $storageUsage->removeUsage(abs($bytes));
            }
        }

        return false;
    }

    /**
     * Dosya yüklemeden önce workspace'in yeterli depolama alanı olup olmadığını kontrol et
     *
     * @param string $fileabletype Dosyanın bağlı olduğu model tipi
     * @param int $fileableid Dosyanın bağlı olduğu model ID'si
     * @param int $filesize Dosya boyutu (byte)
     * @return bool
     */
    public static function hasEnoughStorageSpace(string $fileabletype, int $fileableid, int $filesize): bool
    {
        // Dosyanın bağlı olduğu modeli bul
        $modelClass = $fileabletype;
        $fileable = $modelClass::find($fileableid);

        if (!$fileable) {
            return false;
        }

        // Eğer fileable bir Project veya Task ise, workspace'i bul
        $workspace = null;

        if ($fileable instanceof Project) {
            $workspace = $fileable->workspace;
        } elseif ($fileable instanceof Task) {
            $workspace = $fileable->project->workspace;
        }

        if ($workspace) {
            return $workspace->hasEnoughStorageSpace($filesize);
        }

        return false;
    }

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
