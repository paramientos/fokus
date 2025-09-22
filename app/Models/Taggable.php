<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tag_id
 * @property string $taggable_type
 * @property int $taggable_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Taggable newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Taggable newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Taggable query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Taggable whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Taggable whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Taggable whereTagId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Taggable whereTaggableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Taggable whereTaggableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Taggable whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Taggable extends Model
{
    use HasUuids;

    protected $guarded = [];
}
