# GIS Wrapper Providers
## Lumen
You should be able to use the Provider in Laravel in a similar way
### Installation
1. Include the GIS wrapper `composer require kjschubert/php-gis-wrapper`
2. Register the GIS wrapper in your `app/bootstrap.php`
```
$app->register(\GISwrapper\Providers/Lumen::class);
```

### Configuration
The Lumen Provider uses the following Environment variables
- `GIS_USER` and `GIS_PASS` standard login credentials (must be an EXPA account)
- `NATIONAL_AUTH_URL` url for the AIESEC Identity custom token flow. Use '%USER_ID%' as placeholder for the user ID

### Usage
- get an instance with the standard login credentials: `App::make('GIS')->getInstance()`
- get an instance for a specific user via the AIESEC Identity custom token flow `App::make('GIS')->getInstance($userId)`
- get an instance with specified login credentials `App::make('GIS')->getUserInstance($username, $password)`
    - this uses the AuthProviderCombined
    - you can pass 'expa' or 'op' as third parameter to use those AuthProviders
    - you can also use sessions by providing the session path as first parameter leave the second parameter or set it to `null`
- get the currently used cache `App::make('GIS')->getCache()`

The Lumen Provider automatically uses the caching functionality, by placing a `cache.dat` file in its root folder.