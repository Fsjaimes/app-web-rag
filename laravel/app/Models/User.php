<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    protected $table = 'users';

    /**
     * En tests PHPUnit usa la conexión por defecto (migraciones Jetstream locales).
     * En runtime la app hija lee usuarios del landlord PostgreSQL de Suite.
     */
    public function getConnectionName(): ?string
    {
        return app()->runningUnitTests()
            ? config('database.default')
            : 'landlord';
    }

    protected $fillable = [
        'uuid',
        'role_id',
        'identification_type_id',
        'identification_number',
        'name',
        'other_name',
        'first_last_name',
        'second_last_name',
        'full_name',
        'search_name',
        'email',
        'password',
        'phone_code',
        'phone_number',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $appends = [
        'profile_photo_url',
    ];
}
