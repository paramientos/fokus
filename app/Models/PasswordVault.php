<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 *
 *
 * @property int $id
 * @property int $workspace_id
 * @property int $user_id
 * @property string $name
 * @property string|null $description
 * @property bool $is_shared
 * @property string|null $master_password_hash
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $icon
 * @property string|null $color
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PasswordCategory> $categories
 * @property-read int|null $categories_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PasswordEntry> $entries
 * @property-read int|null $entries_count
 * @property-read bool $is_locked
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Workspace $workspace
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordVault newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordVault newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordVault query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordVault whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordVault whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordVault whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordVault whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordVault whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordVault whereIsShared($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordVault whereMasterPasswordHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordVault whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordVault whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordVault whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordVault whereWorkspaceId($value)
 * @mixin \Eloquent
 */
class PasswordVault extends Model
{
    use HasFactory,HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_id',
        'user_id',
        'name',
        'description',
        'is_shared',
        'master_password_hash',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_shared' => 'boolean',
    ];

    /**
     * Get the workspace that owns the vault.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the user that owns the vault.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the categories for the vault.
     */
    public function categories(): HasMany
    {
        return $this->hasMany(PasswordCategory::class);
    }

    /**
     * Get the password entries for the vault.
     */
    public function entries(): HasMany
    {
        return $this->hasMany(PasswordEntry::class);
    }

    /**
     * Check if the vault has a master password.
     */
    public function hasMasterPassword(): bool
    {
        return !empty($this->master_password_hash);
    }

    /**
     * Verify the master password.
     */
    public function verifyMasterPassword(string $password): bool
    {
        if (!$this->hasMasterPassword()) {
            return true;
        }

        return password_verify($password, $this->master_password_hash);
    }

    /**
     * Set the master password.
     */
    public function setMasterPassword(string $password): void
    {
        $this->update([
            'master_password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);
    }

    /**
     * Check if the vault is locked.
     */
    public function getIsLockedAttribute(): bool
    {
        if (!$this->hasMasterPassword()) {
            return false;
        }

        $unlockTime = session('vault_unlock_' . $this->id);

        if (!$unlockTime) {
            return true;
        }

        // Check if the unlock time is still valid (1 minute)
        if (now()->diffInSeconds($unlockTime,true) > 60) {
            $this->lock();
            return true;
        }

        return false;
    }

    /**
     * Unlock the vault.
     */
    public function unlock(string $password): bool
    {
        if ($this->verifyMasterPassword($password)) {
            session(['vault_unlock_' . $this->id => now()]);
            return true;
        }

        return false;
    }

    /**
     * Lock the vault.
     */
    public function lock(): void
    {
        session()->forget('vault_unlock_' . $this->id);
    }

    /**
     * Extend the unlock time.
     */
    public function extendUnlockTime(): void
    {
        if (!$this->is_locked) {
            session(['vault_unlock_' . $this->id => now()]);
        }
    }
}
