<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name', 'email', 'password', 'company_name', 'company_number', 'company_phone',
    'phone', 'address', 'residency', 'zip', 'locale', 'google_id',
    'needs_role_selection', 'notify_by_email',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasMedia
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, InteractsWithMedia, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'company_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'needs_role_selection' => 'boolean',
            'is_blocked' => 'boolean',
            'notify_by_email' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 200, 200)
            ->nonQueued();
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    /** Orders this user placed on other people's listings. */
    public function placedOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    /** Orders other people placed on this user's listings. */
    public function receivedOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'owner_id');
    }

    public function reviewsReceived(): HasMany
    {
        return $this->hasMany(Review::class, 'subject_id');
    }

    public function reviewsWritten(): HasMany
    {
        return $this->hasMany(Review::class, 'author_id');
    }

    public function blacklistEntries(): HasMany
    {
        return $this->hasMany(Blacklist::class);
    }

    public function verificationCodes(): HasMany
    {
        return $this->hasMany(VerificationCode::class);
    }

    public function role(): ?UserRole
    {
        $name = $this->roles->first()?->name;

        return $name ? UserRole::tryFrom($name) : null;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::Admin->value);
    }

    /** Company accounts must confirm their phone by SMS before using the account area. */
    public function needsPhoneVerification(): bool
    {
        return $this->hasRole(UserRole::Company->value)
            && $this->company_phone !== null
            && $this->phone_verified_at === null;
    }

    public function avatarUrl(): ?string
    {
        return $this->getFirstMediaUrl('avatar', 'thumb') ?: null;
    }

    /** True when this user blocked the given user. */
    public function hasBlocked(User|int $user): bool
    {
        $id = $user instanceof User ? $user->id : $user;

        return $this->blacklistEntries()->where('blocked_user_id', $id)->exists();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin() && ! $this->is_blocked;
    }
}
