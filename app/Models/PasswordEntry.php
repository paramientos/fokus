<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

/**
 * @property string $id
 * @property string $password_vault_id
 * @property string|null $password_category_id
 * @property string $title
 * @property string|null $username
 * @property string $password_encrypted
 * @property string|null $url
 * @property string|null $notes
 * @property array<array-key, mixed>|null $custom_fields
 * @property bool $is_favorite
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $last_used_at
 * @property int $security_level
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\PasswordCategory|null $category
 * @property-read \App\Models\PasswordVault $vault
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordEntry newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordEntry newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordEntry onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordEntry query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordEntry whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordEntry whereCustomFields($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordEntry whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordEntry whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordEntry whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordEntry whereIsFavorite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordEntry whereLastUsedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordEntry whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordEntry wherePasswordCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordEntry wherePasswordEncrypted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordEntry wherePasswordVaultId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordEntry whereSecurityLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordEntry whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordEntry whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordEntry whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordEntry whereUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordEntry withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordEntry withoutTrashed()
 *
 * @mixin \Eloquent
 */
class PasswordEntry extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'password_vault_id',
        'password_category_id',
        'title',
        'username',
        'password_encrypted',
        'url',
        'notes',
        'custom_fields',
        'is_favorite',
        'expires_at',
        'last_used_at',
        'security_level',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_favorite' => 'boolean',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'custom_fields' => 'json',
    ];

    /**
     * Get the vault that owns the password entry.
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(PasswordVault::class, 'password_vault_id');
    }

    /**
     * Get the category that owns the password entry.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(PasswordCategory::class, 'password_category_id');
    }

    /**
     * Set the password attribute.
     */
    public function setPassword(string $password): void
    {
        $this->password_encrypted = Crypt::encryptString($password);
        $this->security_level = $this->calculatePasswordStrength($password);
        $this->save();
    }

    /**
     * Get the decrypted password.
     */
    public function getPassword(): string
    {
        return Crypt::decryptString($this->password_encrypted);
    }

    /**
     * Get the decrypted password.
     */
    public function getDecryptedPassword(): string
    {
        return \Illuminate\Support\Facades\Crypt::decryptString($this->password_encrypted);
    }

    /**
     * Mark the password as used.
     */
    public function markAsUsed(): void
    {
        $this->update([
            'last_used_at' => now(),
        ]);
    }

    /**
     * Check if the password is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Calculate password strength on a scale of 0-5.
     */
    protected function calculatePasswordStrength(string $password): int
    {
        $strength = 0;

        // Length check
        if (strlen($password) >= 8) {
            $strength++;
        }
        if (strlen($password) >= 12) {
            $strength++;
        }

        // Complexity checks
        if (preg_match('/[A-Z]/', $password)) {
            $strength++;
        }
        if (preg_match('/[0-9]/', $password)) {
            $strength++;
        }
        if (preg_match('/[^A-Za-z0-9]/', $password)) {
            $strength++;
        }

        return min(5, $strength);
    }

    /**
     * Get the password strength description.
     */
    public function getStrengthDescription(): string
    {
        return match ($this->security_level) {
            0 => 'Çok Zayıf',
            1 => 'Zayıf',
            2 => 'Orta',
            3 => 'İyi',
            4 => 'Güçlü',
            5 => 'Çok Güçlü',
            default => 'Bilinmiyor',
        };
    }

    /**
     * Get the password strength label.
     */
    public function getStrengthLabel(): string
    {
        return match ($this->security_level) {
            0 => 'Very Weak',
            1 => 'Weak',
            2 => 'Fair',
            3 => 'Good',
            4 => 'Strong',
            5 => 'Very Strong',
            default => 'Unknown',
        };
    }

    /**
     * Get the password strength color.
     */
    public function getStrengthColor(): string
    {
        return match ($this->security_level) {
            0 => 'red-500',
            1 => 'red-400',
            2 => 'yellow-500',
            3 => 'yellow-400',
            4 => 'green-500',
            5 => 'green-400',
            default => 'gray-400',
        };
    }
}
