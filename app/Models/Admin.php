<?php namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use Notifiable;

    protected $table = 'admins';
    public $timestamps = false;
    public $rememberTokenName = null; // legacy table has no remember_token column

    protected $fillable = [
        'username', 'password', 'email', 'role',
        'two_factor_secret', 'two_factor_enabled',
    ];

    protected $hidden = [
        'password', 'two_factor_secret',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'email' => 'encrypted',
        'two_factor_enabled' => 'boolean',
    ];
}
