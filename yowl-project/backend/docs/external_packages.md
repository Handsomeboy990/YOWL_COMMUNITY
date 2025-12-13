# Packages ajoutés au projet

## Laravel Breeze

Initialisation

````
    composer require laravel/breeze --dev

    php artisan:install breeze      # on choisit ensuite l'option Api Only
````

## Spatie permission

Ce package facilite la gestion des roles et permissions. Pour l'initialiser : 

````
    composer require spatie/laravel-permission

    php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
````
