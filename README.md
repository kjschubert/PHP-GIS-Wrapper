# PHP GIS Wrapper
Author: Karl Johann Schubert <karljohann@familieschubi.de>

The PHP GIS Wrapper is a PHP library to connect your PHP projects with AIESEC's Global Information System. It parses the swagger file (Normally: https://gis-api.aiesec.org/v1/docs.json) and thereby always provides you an up-to-date interface to the GIS.

## Quick Start
* copy the folder `gis-wrapper` to your project
* create a `index.php` file with the following content in the project root folder

```php
/*
 * require the Combined Authentication Provider
 * - if you use a predefined active user, use AuthProviderExpa
 * - if you only need OP rights, or have only non-active users, use AuthProviderOP
 */
require_once('./gis-wrapper/AuthProviderCombined.php');

// require GIS wrapper main class
require_once('./gis-wrapper/GIS.php');

// login to GIS and instantiate GIS wrapper
$user = new \GIS\AuthProviderCombined("itguy@aiesec.net", "ITguysKnowThatTheyNeedSuperSecretPasswords!!!11!");
$gis = new \GIS\GIS($user);

// output the full name of the currently logged in person
echo "This data is presented by: ";
foreach($gis->current_person as $p) {
    echo $p->person->full_name . "\n";
}

// short version (because current_person is a non-paged endpoint)
echo "Again this data is presented by: " . $gis->current_person->get()->person->full_name . "\n";

// search for TNs with Marketing in the name which are created in January 2016
$gis->opportunities->q = "Marketing";
$gis->opportunities->filters->created->from = "2016-01-01";
$gis->opportunities->filters->created->to = "2016-01-31";

// output one opportunity per line
foreach($gis->opportunities as $o) {
    echo $o->title . " (" . $o->programmes->short_name . ") by " . $o->branch->name . "\n";
    echo $o->created_at . "\n";
}
```

* open a terminal, go to the project root folder and run `php index.php`

## Roadmap
* add ArrayAccess Interface
* manage dates as parameter values
* introduce write access (PATCH, POST, DELETE)

## Details
### Auth Providers
The file `AuthProvider.php` provides an interface for Authentication Provider. The Purpose of an Authentication Provider is to provide an access token to access the GIS API.

At the moment there are three Authentication Providers:
* `AuthProviderExpa` to get an access token like you would login to EXPA.
* `AuthProviderOP` to get an access token like you would login to OP.
* `AuthProviderCombined` this provider tries to get an EXPA token and if it is invalid returns an OP token.

<i>Hint: If you want to synchronise your national or local systems with the GIS, just create a new account and a new team in your office. Then match the new account as team leader in the new team. Now you can use the credentials of this account and generate access tokens for your sync.</i>

Every Authentication Provider has to provide the function `getToken()` and `getNewToken()` the second function is used by the API wrapper if the API respond with an error, that the access token expired, to try it with a new token. That is useful, when the Authentication Provider caches the access token and has no option to determine if it's still valid.

#### How to choose the right Auth Provider
* if you have a predefined and active user: AuthProviderExpa
* if you only want to authenticate active users: AuthProviderExpa (Remember: If you get an access token this does not mean that the user is active, so if you need to know that use the current_person endpoint to validate the token)
* if you authenticate both active and non-active users: AuthProviderCombined
* if you only need OP rights, or have only non-active users: AuthProviderOP

Try to use AuthProviderExpa and AuthProviderOP as much as possible. The AuthProviderCombined directly gives you the current person object and thereby validates if the token is an EXPA or OP token, but therefore he needs some more requests.

Especially if you want to authenticate only active users, use AuthProviderExpa and validate the token afterwards. The AuthProviderCombined would make a request more, to generate an OP token.

### Endpoints
The class `GIS` is the entry point towards the GIS. At the moment this class provides you access to every GET endpoint, whereby every part of the endpoint path is turned into a subobject (without the version at the beginning). For parameter parts you have to add a underscore before the actual value.

```php
// /v2/applications.json
$gis->applications

// /v2/applications/attachements.json
$gis->applications->attachements

// /v2/applications/8484/comments.json
$gis->applications->_8484->comments
```

Through every Endpoint implements the Iterator interface you can just iterator with a foreach loop over all the elements of a paged endpoint. For non-paged endpoints you can use a foreach loop, which then only runs once, or you can use the `get()` function to get the current and only element.

You can find the available endpoints at https://apidocs.aies.ec by clicking on a section. Please note, that at the moment only GET endpoints are supported.

Please also note that by default the documentation for v1 is showed. The PHP GIS Wrapper uses version 2 by default. To view the documentation for v2, go to the url in the navbar and change the v1 to v2.

### Parameters
You can directly set/get every filter as subobject of an endpoint, e.g. `$gis->opportunities->q = "Marketing";`. If the parameter name is in array notation every level becomes a subobject named by the key.

```php
// /v2/opportunities.json (filters[created][from])
$gis->opportunities->filters->created->from = "2016-01-01"

// /v2/opportunities.json (filters[programmes])
$gis->opportunities->filters->programmes = array(1);
```

The filters are saved in the endpoint, thereby you can access the filtered data like shown in the section Endpoints. If you want to use an endpoint with completly different parameters, you can also use the `reset()` function.

```php
$gis->opportunities->reset();
```

You can find the available parameters at https://apidocs.aies.ec by clicking on a section and then on an endpoint. Please note, that at the moment only GET endpoints are supported and that parameters which are part of the endpoint URL become part of the object path in PHP, thereby you can not set them like shown here.

Please also note that by default the documentation for v1 is showed. The PHP GIS Wrapper uses version 2 by default. To view the documentation for v2, go to the url in the navbar and change the v1 to v2.

## Testing
<b>Attention:</b> The Tests are not yet updated to the API version 2. Through the PHP GIS Wrapper already uses v2 by default, they might fail.

This project uses phpunit (https://phpunit.de) for testing. All the UnitTests are in the folder `tests`. This folder also contains the folder `CodeCoverage` which contains the CodeCoverage report in html format for the tests in the repository.

If you add further UnitTests and want to run them, install phpunit, go to the project root folder and run `phpunit --coverage-html tests/CodeCoverage --disallow-test-output tests`

To run the given tests please copy the file `config.example.php` to `config.php` and insert a valid username and password.