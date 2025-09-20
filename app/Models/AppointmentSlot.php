<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 *
 *
 * @property int $id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon $date
 * @property string $start_time
 * @property string $end_time
 * @property bool $is_available
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Appointment|null $appointment
 * @property-read int $duration_in_minutes
 * @property-read string $formatted_time
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentSlot newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentSlot newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentSlot query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentSlot whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentSlot whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentSlot whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentSlot whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentSlot whereIsAvailable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentSlot whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentSlot whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentSlot whereUserId($value)
 * @mixin \Eloquent
 */
class AppointmentSlot extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = [
        'user_id',
        'date',
        'start_time',
        'end_time',
        'is_available',
    ];

    protected $casts = [
        'date' => 'date',
        'is_available' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appointment(): HasOne
    {
        return $this->hasOne(Appointment::class, 'slot_id');
    }

    public function isBooked(): bool
    {
        return $this->appointment()->exists();
    }

    public function getFormattedTimeAttribute(): string
    {
        return date('H:i', strtotime($this->start_time)) . ' - ' . date('H:i', strtotime($this->end_time));
    }

    public function getDurationInMinutesAttribute(): int
    {
        $start = \Carbon\Carbon::parse($this->start_time);
        $end = \Carbon\Carbon::parse($this->end_time);

        return $end->diffInMinutes($start);
    }
}
