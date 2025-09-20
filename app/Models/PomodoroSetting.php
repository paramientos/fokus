<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property string $id
 * @property string $user_id
 * @property string|null $workspace_id
 * @property bool $notification
 * @property bool $sound
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroSetting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroSetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroSetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroSetting whereNotification($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroSetting whereSound($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroSetting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroSetting whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PomodoroSetting whereWorkspaceId($value)
 * @mixin \Eloquent
 */
class PomodoroSetting extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = [
        'user_id',
        'workspace_id',
        'notification',
        'sound',
    ];
}
