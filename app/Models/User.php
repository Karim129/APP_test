<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'nickname',
        'phone',
        'avatar',
        'bio',
        'profile_picture',
        'privacy_settings',
        'is_active',
        'last_login_at',
        'last_login_ip',
        'last_login_device',
        'failed_login_attempts',
        'locked_until',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
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
            'is_active' => 'boolean',
            'privacy_settings' => 'array',
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',
        ];
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasRole('Admin') || $this->hasRole('Seller');
    }

    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    public function hasPermission(string $permission): bool
    {
        return $this->roles->flatMap(function ($role) {
            return $role->permissions ?? [];
        })->contains($permission);
    }

    public function assignRole(string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();
        if ($role && !$this->hasRole($roleName)) {
            $this->roles()->attach($role->id);
        }
    }

    public function removeRole(string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();
        if ($role) {
            $this->roles()->detach($role->id);
        }
    }

    public function ownedTeams()
    {
        return $this->hasMany(Team::class, 'owner_id');
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class);
    }

    public function ownedGroups()
    {
        return $this->hasMany(Group::class, 'owner_id');
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function offers()
    {
        return $this->hasMany(Offer::class, 'seller_id');
    }

    public function badges()
    {
        return $this->belongsToMany(Badge::class, 'user_badges');
    }

    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    public function emergencyContacts()
    {
        return $this->hasMany(EmergencyContact::class);
    }
}
