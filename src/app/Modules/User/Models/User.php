<?php

declare(strict_types=1);

namespace App\Modules\User\Models;

use App\Modules\User\Enums\UserRole;
use App\Modules\User\Notifications\SendWelcomeSms;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * @property int              $id
 * @property string           $name
 * @property string           $email
 * @property string|null      $phone     Телефон в формате E.164 (+79991234567)
 * @property string           $password
 * @property UserRole         $role
 * @property \Carbon\Carbon   $created_at
 * @property \Carbon\Carbon   $updated_at
 */
class User extends Authenticatable implements JWTSubject
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'role'              => UserRole::class,
        ];
    }

    public function getJWTIdentifier(): int|string
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'role' => $this->role->value,
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role->isAdmin();
    }

    protected static function newFactory()
    {
        return \Database\Factories\UserFactory::new();
    }
}
