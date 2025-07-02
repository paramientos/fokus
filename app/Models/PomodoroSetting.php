<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $workspace_id
 * @property int $notification
 * @property int $sound
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
    use HasFactory;

    protected $fillable = [
        'user_id',
        'workspace_id',
        'notification',
        'sound',
    ];
}
