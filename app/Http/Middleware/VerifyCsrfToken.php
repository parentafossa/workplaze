<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
        '/D12uOKH4DChBJ0IHrtCzt9pM72aV4txcHsNzzfFbDtHjATqZ/webhook',
        '/chat-bot',
        '/chat-reply',
        'auth/google/callback',
    ];
}
