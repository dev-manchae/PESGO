<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use InvalidArgumentException;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
        ];
    }

    /**
     * Get the user's profile.
     */
    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class, 'user_id');
    }

    /**
     * Get the user's addresses.
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class, 'user_id');
    }

    /**
     * The roles that belong to the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id')
            ->withPivot('created_at');
    }

    /**
     * Check if the user has a specific role.
     */
    public function hasRole(RoleSlug|string $role): bool
    {
        $roleSlug = $role instanceof RoleSlug ? $role : RoleSlug::tryFrom($role);

        if ($roleSlug === null) {
            return false;
        }

        if ($this->relationLoaded('roles')) {
            return $this->roles->contains(function (Role $r) use ($roleSlug) {
                return $r->slug === $roleSlug || $r->slug?->value === $roleSlug->value;
            });
        }

        return $this->roles()->where('slug', $roleSlug->value)->exists();
    }

    /**
     * Check if the user has any of the given roles.
     *
     * @param  array<RoleSlug|string>  $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the user is an Administrator.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(RoleSlug::ADMIN);
    }

    /**
     * Check if the user is a Personal Shopper.
     */
    public function isShopper(): bool
    {
        return $this->hasRole(RoleSlug::SHOPPER);
    }

    /**
     * Check if the user is a Customer.
     */
    public function isCustomer(): bool
    {
        return $this->hasRole(RoleSlug::CUSTOMER);
    }

    /**
     * Assign a role to the user idempotently.
     */
    public function assignRole(RoleSlug|string|Role $role): void
    {
        $roleModel = $role instanceof Role
            ? $role
            : Role::where('slug', ($role instanceof RoleSlug ? $role->value : $role))->first();

        if (! $roleModel) {
            $identifier = $role instanceof RoleSlug ? $role->value : (string) $role;
            throw new InvalidArgumentException("Role [{$identifier}] does not exist.");
        }

        $this->roles()->syncWithoutDetaching([$roleModel->id]);
        $this->unsetRelation('roles');
    }

    /**
     * Remove a role from the user.
     */
    public function removeRole(RoleSlug|string|Role $role): void
    {
        $roleModel = $role instanceof Role
            ? $role
            : Role::where('slug', ($role instanceof RoleSlug ? $role->value : $role))->first();

        if ($roleModel) {
            $this->roles()->detach($roleModel->id);
            $this->unsetRelation('roles');
        }
    }
}
