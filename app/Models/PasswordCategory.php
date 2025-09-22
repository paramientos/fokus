<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $password_vault_id
 * @property string $name
 * @property string $color
 * @property string $icon
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PasswordEntry> $entries
 * @property-read int $entries_count
 * @property-read \App\Models\PasswordVault $vault
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordCategory whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordCategory whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordCategory wherePasswordVaultId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordCategory whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class PasswordCategory extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'password_vault_id',
        'name',
        'color',
        'icon',
    ];

    /**
     * Get the vault that owns the category.
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(PasswordVault::class, 'password_vault_id');
    }

    /**
     * Get the password entries for the category.
     */
    public function entries(): HasMany
    {
        return $this->hasMany(PasswordEntry::class);
    }

    /**
     * Get the count of entries in this category.
     */
    public function getEntriesCountAttribute(): int
    {
        return $this->entries()->count();
    }
}
