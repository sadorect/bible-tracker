<?php

/*
|--------------------------------------------------------------------------
| Central Dashboard SSO (dashboard.sadorect.com)
|--------------------------------------------------------------------------
|
| Installed by AppDash. Accepts a short-lived signed token from the central
| dashboard and logs the named user into this app's admin area.
|
| This never creates users and never grants privileges. The user must already
| exist here AND already pass the admin check below.
|
| KILL SWITCH: remove DASHBOARD_SSO_SECRET from .env (then `php artisan
| config:clear`) and the route stops existing.
|
*/

return [

    'secret' => env('DASHBOARD_SSO_SECRET'),

    // Where to send the user once authenticated when the token carries no
    // landing path. Normally the dashboard's own admin URL for this app (edited
    // there, not here) travels inside the signed token and wins over this.
    'redirect_to' => env('DASHBOARD_SSO_REDIRECT', '/admin'),

    /*
    | How this app decides somebody is an admin. Must mirror the check the app
    | already enforces on its own admin routes -- SSO is a different door into
    | the same room, not a wider one.
    |
    |   filament:admin        Filament panel id; uses canAccessPanel()
    |   method:isAdmin        A boolean method on the User model
    |   attribute:is_admin    A truthy column on the users table
    |   role:admin,super_admin  Spatie hasAnyRole()
    |   permission:access admin Spatie can()
    |   gate:manage-admin     A Gate ability
    |
    | There is no default. An unset value denies everyone, on purpose.
    */
    'admin_check' => env('DASHBOARD_ADMIN_CHECK'),

    // Tokens older than this are refused regardless of their own exp claim.
    'max_age' => 120,

];
