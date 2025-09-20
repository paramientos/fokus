<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 *
 *
 * @property int $id
 * @property int $project_id
 * @property int $from_status_id
 * @property int $to_status_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Status $fromStatus
 * @property-read \App\Models\Project $project
 * @property-read \App\Models\Status $toStatus
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusTransition newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusTransition newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusTransition query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusTransition whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusTransition whereFromStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusTransition whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusTransition whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusTransition whereToStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusTransition whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class StatusTransition extends Model
{
    use HasUuids;

    protected $table='status_transitions';

    protected $fillable = ['project_id', 'from_status_id', 'to_status_id'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function fromStatus()
    {
        return $this->belongsTo(Status::class, 'from_status_id');
    }

    public function toStatus()
    {
        return $this->belongsTo(Status::class, 'to_status_id');
    }
}
