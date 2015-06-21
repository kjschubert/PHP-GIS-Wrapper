# PHP GIS Wrapper
Author: Karl Johann Schubert <karljohann.schubert@aiesec.de>, AIESEC in Germany

The PHP GIS Wrapper is a PHP library to connect your PHP projects with AIESEC's Global Information System. It parses the swagger file (Normally: https://gis-api.aiesec.org/v1/docs.json) and thereby always provides you an up-to-date interface to the GIS.

## Quick Start
* copy the folder `gis-wrapper` to your project
* create a `index.php` file with the following content in the project root folder

```php
// require the Authentication Provider vor GIS Auth login credentials and the GIS wrapper main class
require_once('./gis-wrapper/AuthProviderUser.php');
require_once('./gis-wrapper/GIS.php');

// login to GIS and instantiate GIS wrapper
$user = new \GIS\AuthProviderUser("itguy@aiesec.net", "ITguysKnowThatTheyNeedSuperSecretPasswords!!!11!");
$gis = new \GIS\GIS($user);

// output the full name of the currently logged in person
echo "This data is presented by: ";
foreach($gis->current_person as $p) {
    echo $p->person->full_name . "\n";
}

// search for TNs with Marketing at DP DHL Group
$gis->opportunities->q = "Marketing";
$gis->opportunities->filters->company = 286836;

// output one opportunity per line
foreach($gis->opportunities as $o) {
    echo $o->title . " (" . $o->programmes[0]->short_name . ") by " . $o->branch->name . "\n";
}
```

* open a terminal, go to the project root folder and run `php index.php`

## Roadmap
* add ArrayAccess Interface
* manage dates as parameter values
* introduce write access (PATCH, POST, DELETE)
* helper classes for specific endpoints

## Details
### Auth Providers
The file `AuthProvider.php` provides an interface for Authentication Provider. Purpose of a Authentication Provider is to provide an access token to access the GIS API.

At the moment there is only the Auth Provider `AuthProviderUser` which is used to authenticate with GIS auth credentials. In Germany we have a dedicated Bot account on MC level to synchronise data between the GIS and our SalesForce instance.

Every Authentication Provider has to provide the function `getToken()` and `getNewToken()` the second function is used by the API wrapper if the API respond with an error, that the access token expired, to try it with a new token. That is useful, when the Authentication Provider caches the access token and has no option to determine if it's still valid.

### Endpoints
The class `GIS` is the entry point towards the GIS. At the moment this class provides you access to every GET endpoint, whereby every part of the endpoint path is turned into a subobject (without the version at the beginning). For parameter parts you have to add a underscore before the actual value.

```php
// /v1/applications.json
$gis->applications

// /v1/applications/basic_analytics.json
$gis->applications->basic_analytics

// /v1/applications/8484/comments.json
$gis->applications->_8484->comments
```

Through every Endpoint implements the Iterator interface you can just iterator with a foreach loop over all the elements of a paged endpoint. For non-paged endpoints you can use a foreach loop, which then only runs once, or you can use the `get()` function to get the current and only element.

You can find the available endpoints at https://apidocs.aies.ec by clicking on a section. Please note, that at the moment only GET endpoints are supported.

### Parameters
You can directly set/get every filter as subobject of an endpoint, e.g. `$gis->opportunities->q = "Marketing";`. If the parameter name is in array notation every level becomes a subobject named by the key.

```php
// /v1/opportunities.js (filters[company])
$gis->opportunities->filters->company = 286836

// /v1/opportunities.js (filters[programmes])
$gis->opportunities->filters->programmes = array(1);
```

The filters are saved in the endpoint, thereby you can access the filtered data like shown in the section Endpoints. If you want to use an endpoint with completly different parameters, you can also use the `reset()` function.

```php
$gis->opportunities->reset();
```

You can find the available parameters at https://apidocs.aies.ec by clicking on a section and then on an endpoint. Please note, that at the moment only GET endpoints are supported and that parameters which are part of the endpoint URL become part of the object path in PHP, thereby you can not set them like shown here.

## Testing
This project uses phpunit (https://phpunit.de) for testing. All the UnitTests are in the folder `tests`. This folder also contains the folder `CodeCoverage` which contains the CodeCoverage report in html format for the tests in the repository.

If you add further UnitTests and want to run them, install phpunit, go to the project root folder and run `phpunit --coverage-html tests/CodeCoverage --disallow-test-output tests`

To run the given tests please copy the file `config.example.php` to `config.php` and insert a valid username and password.