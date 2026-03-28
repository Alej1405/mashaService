<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate;

class MobileAuthenticate extends Authenticate
{
    protected function redirectTo(\Illuminate\Http\Request $request): ?string
    {
        return $request->expectsJson() ? null : route('mobile.login');
    }
}
