<?php

namespace App\Models;

use App\Enums\UserStatus;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'status',
        'avatar',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'id',
        'password',
        'remember_token',
        'email_verified_at',
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

    public function userProviders(): HasMany
    {
        return $this->hasMany(UserProvider::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    public function mustVerifyEmail(): bool
    {
        return $this instanceof MustVerifyEmail && !$this->hasVerifiedEmail();
    }

    public function createDeviceToken(string $device, string $ip, bool $remember = false): string
    {
        $sanctumToken = $this->createToken(
            $device,
            ['*'],
            $remember ?
                now()->addMonth() :
                now()->addDay()
        );

        $sanctumToken->accessToken->ip = $ip;
        $sanctumToken->accessToken->save();

        return $sanctumToken->plainTextToken;
    }

    public function scopeActive($query)
    {
        return $query->where('status', UserStatus::ACTIVE);
    }

    public function scopePending($query)
    {
        return $query->where('status', UserStatus::PENDING);
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', UserStatus::SUSPENDED);
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::ACTIVE;
    }

    public function isPending(): bool
    {
        return $this->status === UserStatus::PENDING;
    }

    public function isSuspended(): bool
    {
        return $this->status === UserStatus::SUSPENDED;
    }
}
