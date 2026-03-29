<?php

return [
    'admin_email' => env('BOB1_USER_EMAIL'),

    /*
    |--------------------------------------------------------------------------
    | Pagination — Index Views
    |--------------------------------------------------------------------------
    |
    | Number of records shown per page on all index (listing) views across
    | the app — lists, feeds, output destinations, language models, etc.
    |
    */
    'pagination_index' => env('ADMIN_PAGINATION_INDEX', 20),

    /*
    |--------------------------------------------------------------------------
    | Pagination — Show View Relationships
    |--------------------------------------------------------------------------
    |
    | Number of related records shown per page in the relationship tables
    | on show views (e.g. sources on a list, lists on a channel, etc.).
    |
    */
    'pagination_show' => env('ADMIN_PAGINATION_SHOW', 10),

    /*
    |--------------------------------------------------------------------------
    | Allow db:seed to seed
    |--------------------------------------------------------------------------
    |
    | Explicit permission is needed to seed database. 
    | For the express purpose of preventing seeding in production.
    | Used in databases/seeders/DatabaseSeeder.php
    |
    */
    'seeding_enabled' => env('ADMIN_SEEDING_ENABLED', false),
];