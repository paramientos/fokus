<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $id
 * @property string $project_id
 * @property string $name
 * @property string $slug
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $type Kategori tipi (status, type, technical, user)
 * @property string|null $description Kategori açıklaması
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WikiPage> $pages
 * @property-read int|null $pages_count
 * @property-read \App\Models\Project $project
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WikiCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WikiCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WikiCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WikiCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WikiCategory whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WikiCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WikiCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WikiCategory whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WikiCategory whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WikiCategory whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WikiCategory whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class WikiCategory extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'project_id',
        'name',
        'slug',
        'type',
        'description',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(WikiPage::class, 'wiki_category_wiki_page');
    }
}
