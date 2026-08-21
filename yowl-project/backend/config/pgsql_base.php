<?php

/*
|--------------------------------------------------------------------------
| Base commune aux deux connexions PostgreSQL
|--------------------------------------------------------------------------
|
| Partagee par 'pgsql', qui passe par le pooler, et 'pgsql_unpooled', qui
| l'evite pour les migrations. Ecrire les deux a la main laisserait l'une
| diverger de l'autre au premier ajout.
|
*/

return [
            'driver' => 'pgsql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', env('PGHOST', '127.0.0.1')),
            'port' => env('DB_PORT', env('PGPORT', '5432')),
            'database' => env('DB_DATABASE', env('PGDATABASE', 'laravel')),
            'username' => env('DB_USERNAME', env('PGUSER', 'root')),
            'password' => env('DB_PASSWORD', env('PGPASSWORD', '')),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => env('DB_SSLMODE', env('PGSSLMODE', 'prefer')),
        ];
