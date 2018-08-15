<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;

class VerifyCsrfToken extends BaseVerifier
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        '/rest/answer', '/rest/auth/login', '/rest/auth/logout', '/rest/basket/save', '/rest/question/save', '/db/save', '/db/delete'
    ];
}
