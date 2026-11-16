<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Request ID prefix
    |--------------------------------------------------------------------------
    |
    | Every ID starts with the server code and the project code, then a hyphen
    | and 10 random characters: SRVAPP-K7Q2M9X4TB. The server code is the same
    | for every app on a server, so set it in the environment. The project code
    | is a constant of the app: publish this file and write it here.
    |
    */

    'server_code' => env('APP_SERVER_CODE', 'XXX'),

    'project_code' => env('APP_PROJECT_CODE', 'XXX'),

    /*
    |--------------------------------------------------------------------------
    | Time shown in the error page block
    |--------------------------------------------------------------------------
    |
    | A null timezone means the timezone of the app (app.timezone).
    |
    */

    'timezone' => null,

    'time_format' => 'd.m.Y H:i:s',

];
