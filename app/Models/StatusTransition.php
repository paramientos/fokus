<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusTransition extends Model
{
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
