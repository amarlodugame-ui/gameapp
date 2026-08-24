<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson() && $request->header('X-User-Spa') !== '1') {
            $hashPath = RedirectLegacyUserPage::hashPathForRouteName($request->route()?->getName());

            if ($hashPath !== null) {
                $query = $request->getQueryString();

                return '/#'.$hashPath.($query ? '?'.$query : '');
            }

            return route('user.login');
        }
    }
}
