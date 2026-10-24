<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Written by the consent banner in plain JS, so it must not be encrypted
        // on the way in. It holds no secret - two booleans and a version.
        'consent',
    ];
}
