<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 *
 *
 * @property int $id
 * @property string $filename
 * @property string $path
 * @property string $mime_type
 * @property int $size File size in bytes
 * @property string|null $description
 * @property int|null $user_id
 * @property string $attachable_type
 * @property int $attachable_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model|\Eloquent $attachable
 * @property-read string $extension
 * @property-read string $formatted_size
 * @property-read string $icon_class
 * @property-read bool $is_image
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment whereAttachableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment whereAttachableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment whereFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment whereMimeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment whereUserId($value)
 * @mixin \Eloquent
 */
class Attachment extends Model
{
    use HasFactory,HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'filename',
        'path',
        'mime_type',
        'size',
        'description',
        'user_id',
    ];

    /**
     * Get the parent attachable model.
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user that uploaded the attachment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the formatted file size.
     *
     * @return string
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get the file extension.
     *
     * @return string
     */
    public function getExtensionAttribute(): string
    {
        return pathinfo($this->filename, PATHINFO_EXTENSION);
    }

    /**
     * Check if the file is an image.
     *
     * @return bool
     */
    public function getIsImageAttribute(): bool
    {
        return in_array($this->mime_type, [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/svg+xml',
            'image/webp',
        ]);
    }

    /**
     * Get the file type icon class based on mime type or extension.
     *
     * @return string
     */
    public function getIconClassAttribute(): string
    {
        if ($this->is_image) {
            return 'fas fa-image text-primary';
        }

        $extension = $this->extension;

        if (in_array($extension, ['pdf'])) {
            return 'fas fa-file-pdf text-error';
        } elseif (in_array($extension, ['doc', 'docx'])) {
            return 'fas fa-file-word text-info';
        } elseif (in_array($extension, ['xls', 'xlsx'])) {
            return 'fas fa-file-excel text-success';
        } elseif (in_array($extension, ['zip', 'rar', 'tar', 'gz'])) {
            return 'fas fa-file-archive text-warning';
        } elseif (in_array($extension, ['mp3', 'wav', 'ogg'])) {
            return 'fas fa-file-audio text-secondary';
        } elseif (in_array($extension, ['mp4', 'avi', 'mov', 'wmv'])) {
            return 'fas fa-file-video text-accent';
        } elseif (in_array($extension, ['txt', 'md'])) {
            return 'fas fa-file-alt text-info';
        } elseif (in_array($extension, ['js', 'php', 'html', 'css', 'py', 'java', 'c', 'cpp'])) {
            return 'fas fa-file-code text-primary';
        }

        return 'fas fa-file text-gray-500';
    }
}
