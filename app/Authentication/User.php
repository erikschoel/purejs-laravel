<?php

namespace App\Authentication;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Auth\GenericUser;

class User extends GenericUser
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

    protected $map = [
        'maus_id' => 'user_id',
        'maus_username' => 'user_name'
    ];

    public function getAttributes()
    {
        $result = [];
        if (isset($this->attributes)) {
            foreach($this->attributes as $key => $value) {
                if (isset($this->map[$key])) {
                    $result[$this->map[$key]] = $value;
                }
            }
        }else {
            $result = array('user_id' => 0);
        }
        return $result;
    }

    /**
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'maus_id';
    }

    /**
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        // Return the unique identifier for the user (e.g. their ID, 123)
        return $this->attributes['maus_id'];
    }

    /**
     * @return string
     */
    public function getAuthPassword()
    {
        // Returns the (hashed) password for the user
        return $this->attributes['maus_password'];
    }

}