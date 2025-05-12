<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\CallLog;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * @method bool hasRole(string|array|\Spatie\Permission\Contracts\Role $roles, string|null $guard = null)
 * @method bool hasAnyRole(string|array|\Spatie\Permission\Contracts\Role ...$roles)
 * @method bool hasAllRoles(string|array|\Spatie\Permission\Contracts\Role ...$roles)
 * @method \Spatie\Permission\Contracts\Role|\Illuminate\Database\Eloquent\Collection getRoleNames()
 * @mixin \Spatie\Permission\Traits\HasRoles
 */
class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * The attributes that should be mutated to dates.
     * Add deleted_at for soft deletes.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'team_id',
        'manages_team_id',
        'pancake_uuid',
        'pancake_care_uuid',
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
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Lấy người quản lý của user này (nếu là staff).
     * Liên kết team_id của staff với manages_team_id của manager.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'team_id', 'manages_team_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function callLogs()
    {
        return $this->hasMany(CallLog::class);
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
