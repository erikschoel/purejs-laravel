<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'maus_username', 'maus_password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'maus_password', 'remember_token',
    ];

    /**
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'maus_id';
    }

    /**
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->attributes['maus_password'];
    }
}
