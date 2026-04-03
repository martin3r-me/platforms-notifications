<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Eloquent-Events, die getrackt werden sollen
    |--------------------------------------------------------------------------
    |
    | Hier listet ihr die Laravel-Events auf, z.B.:
    */
    'events' => [
        'created',
        'updated',
        'deleted',
    ],

    /*
    |--------------------------------------------------------------------------
    | Attribute, die beim Tracken ignoriert werden sollen
    |--------------------------------------------------------------------------
    |
    | Diese Felder werden aus dem `properties`-Array herausgefiltert.
    */
    'ignore_attributes' => [
        'created_at',
        'updated_at',
    ],

    /*
    |--------------------------------------------------------------------------
    | Soll das Benachrichtigungs-Modal eingebunden werden?
    |--------------------------------------------------------------------------
    | Über .env steuerbar: NOTIFICATIONS_SHOW_MODAL=true/false
    */
    'show_modal' => env('NOTIFICATIONS_SHOW_MODAL', true), // true als Default

    /*
    |--------------------------------------------------------------------------
    | Pushover
    |--------------------------------------------------------------------------
    | Plattformweiter App-Token. User-Keys werden pro User in der DB gespeichert.
    */
    'pushover' => [
        'app_token' => env('PUSHOVER_APP_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | MS Teams Webhook
    |--------------------------------------------------------------------------
    */
    'teams_webhook' => [
        'enabled' => env('TEAMS_WEBHOOK_ENABLED', false),
    ],

];
