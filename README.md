# PHP GIS Wrapper
The PHP GIS Wrapper is a PHP library to connect your PHP projects with AIESEC's Global Information System.

It gives you the possibility to access every resource in the GIS as if it would be a native object in your php script. You don't need to take care about any requests, parsing or the token generation. Just instantiate an Authentication Provider with a user name and password. Then instantiate the GIS Wrapper and you are ready to go.

If you already used the PHP GIS Wrapper v0.1 please be aware that v0.2 is a complete rewrite. There are a lot of architectural changes whereby the update of your projects is most probably not that simple. The new version definitely gives you a performance boost and brings many new possibilities. Please check the changelog for further informations. If you need help do not hesitate to drop me a message.

- author: Karl Johann Schubert <karljohann@familieschubi.de>
- version: 0.2

# Documentation
Please check the examples folder for a quick start. The explanations below are more general.

## Installation
1. install composer (https://getcomposer.org/)
2. `composer require kjschubert/php-gis-wrapper`
3. require the composer autoloader in your scripts

## AuthProviders
The file `AuthProvider.php` provides an interface for Authentication Providers. The Purpose of an Authentication Provider is to provide an access token to access the GIS API.

At the moment there are three main Authentication Providers:
* `AuthProviderEXPA($username, $password)` to get an access token like you would login to EXPA.
* `AuthProviderOP($username, $password)` to get an access token like you would login to OP.
* `AuthProviderCombined($username, $password)` this provider tries to get an EXPA token and if it is invalid returns an OP token.

Furthermore there are two special Authentication Providers
* `AuthProviderShadow($tokan, $authProvider)` which takes a valid token as first argument and another AuthProvider as second argument. You may use this AuthProvider when you cache tokens.
* `AuthProviderNationalIdentity($url)` which can be used with the customTokenFlow of the [AIESEC Identity](https://github.com/AIESEC-Egypt/aiesec-identity) Project.

<i>Hint: If you want to synchronise your national or local systems with the GIS, just create a new account and a new team in your office. Then match the new account as team leader in the new team. Now you can use the credentials of this account and generate access tokens for your sync.</i>

Every Authentication Provider has to provide the function `getToken()` and `getNewToken()` the second function is used by the API wrapper if the API responds with an error, that the access token expired, to try it with a new token. That is useful, when the Authentication Provider caches the access token and has no option to determine if it's still valid.

### How to choose the right main Auth Provider
* if you have a predefined and active user: AuthProviderEXPA
* if you only want to authenticate active users: AuthProviderEXPA (Remember: If you get an access token this does not mean that the user is active, so if you need to know that use the current_person endpoint to validate the token)
* if you authenticate both active and non-active users: AuthProviderCombined
* if you only need OP rights, or have only non-active users: AuthProviderOP

Try to use AuthProviderEXPA and AuthProviderOP as much as possible. The AuthProviderCombined directly gives you the current person object and thereby validates if the token is an EXPA or OP token, but therefore he needs some more requests.

Especially if you want to authenticate only active users, use AuthProviderEXPA and validate the token afterwards. The AuthProviderCombined would make a request more, to generate an OP token.

### Keep the GIS Identity session
When a user access one of the frontends of the GIS he is redirected to the GIS Identity Service at auth.aiesec.org. This service opens a session for the user, whereby he do not need to login twice when he access another frontend. By now all three main Authentication Providers can make use of this session. On the one hand this can improve the performance of your script. On the other hand you can also generate an access token without saving the user credentials, just by keeping the session file.

You can set the filepath of the session via the function `setSession($path)`. The function `getSession()` returns the current session path. The session file must not exist beforehand, but the directory and the file must be writeable for PHP.

If you want to generate an access token from an existing session without having the user credentials, instantiate on of the standard AuthProviders with the filepath to the session as first parameter and leave the second parameter empty or set it to null. When the session file does not exist this will produce a E_USER_ERROR php error. If the session is invalid the generation of a token will throw a InvalidCredentials Exception.

Please make sure to call the function `setSession($path)` before you generate any access token. Everything else will work, but could lead to a inconsistent behaviour.

### helper functions
- All three main Authentication Providers support a boolean as third argument for the constructor. Setting this argument to false will disable the SSL Peer Verification. Set the second argument to `null` if you instantiate the AuthProvider with a session
- All three main Authentication Providers provide the function `getExpiresAt()`, which returns the timestamp until when the current access token is valid.
- The `AuthProviderCombined` furthermore provides:
    - the functions `isOP()` and `isEXPA()` which return a bool depending on the scope of the token
    - the function `getType()` which returns 'EXPA' or 'OP' depending on the scope of the token
    - the function `getCurrentPerson()` which returns the current person object, because it have to load this to validate the token
- The `AuthProviderShadow` provides the function `getAuthProvider()`, which returns the underlaying AuthProvider or null

## Class GIS
The class GIS is the entry point to access AIESECs Global Information System from your project. The first argument must be an AuthProvider. The second parameter can either be empty, or the url of the API documentation, or an array containing the already parsed API documentation.

For simple projects it is fine to leave the second argument empty.
```
$user = new \GISwrapper\AuthProviderEXPA($username, $password);
$gis = new \GISwrapper\GIS($user);
```
If you want to improve the performance of your project read more in the paragraph caching.

### Caching
The GIS API is documented in the swagger format. Normally the GIS wrapper downloads those files and parses them with every instantiation. If your project is using the GIS on a higher scale you can retrieve the parsing result and cache it by yourself.
- `\GISwrapper\GIS::generateSimpleCache()` returns an array with just the parsed root swagger file
- `\GISwrapper\GIS::generateFullCache()` returns an array with all endpoints parsed

Both functions can take a alternative link to the API documentation as first argument.

You can instantiate the GIS class with the returned array as second parameter. Please check the examples folder for an example script on how to cache the full cache in a file.

## Data Access and Manipulation
Please check the api documentation at http://apidocs.aies.ec/ to get to know which endpoints exists. (<b>Attention:</b> make sure to change the file to the docs.json from v1 to v2)

Starting from your instance of the GIS (e.g. $gis) every static part after /v2/ of the path is turned into an object. Every dynamic part turns the previous part into an array, whereby the array key represents the value for the dynamic part.
```
// /v2/opportunities.json
$gis->opportunities;

// /v2/opportunities/{opportunity_id}.json
$gis->opportunities[opportunity_id]

// /v2/opportunities/{opportunity_id}/progress.json
$gis->opportunities[opportunity_id]->progress
```

### Getting data
There are two different kinds of endpoints. Those who return just one resource like /v2/current_person.json and those who return different pages each with a list of resources.

To get data from the fist kind, just call the get method.
```
// /v2/current_person.json
$res = $gis->current_person->get();
print_r($res);
```

The second kind of endpoint is accessable via an Iterator, so most probalby you want to use an foreach loop.
```
// /v2/opportunities.json
foreach($gis->opportunities as $o) {
    print_r($o);
}
```

### Create a resource
Please check the paragraph Parameters to get to know how to access the parameters of an endpoint. After you set all parameters which are necessary to create a new object call the `update()` function on that endpoint.

Please check the examples folder for an script on how to create, update and delete a new opportunity.

Endpoints who support the creation of a new object are those who support the http method POST. Please check the respective endpoint documentation for the required parameters.

### Update an existing resource
After setting the necessary parameters on the endpoint you want to update, call the `update()` method on that endpoint.

Please check the examples folder for an script on how to create, update and delete a new opportunity.

Endpoints who support updates, are those which support the http method PATCH. Please check the respective endpoint documentation for the required parameters.

### Delete a resource
To delete an resource call the `delete()` method on that endpoint.

Endpoint who support the delete methode are those which support the http method DELETE. Please check the api documentation to find those endpoints.

## Parameters
Every Endpoint on the GIS API has parameters. Some parameters are already part of the path. Like already described those parameters turn into array keys.

The GIS wrapper already takes care of the parameters access_token, page and per_page. Thereby you can not access or change them.

All other parameters of the parameter type query and form turn into subobjects of the endpoint. The Array notation in the documentation is translated into the object notation in php. So in general every key mentioned in the documentation becomes a subobject, whereby the array notation in php is used for the different elements of an parameter with the data type array.

Let's take a look at the endpoint `/v2/opportunities.json`
```
$gis->opportunities->q = "some String"; // set parameter q
$gis->opportunities->filters->organisation = 10 // set parameter filters[organisation]
$gis->opportunities->filters->issues[0] = 10 // set element 0 of the array parameter filters[issues]
$gis->opportunities->filters->issues[1] = 20 // set element 1 of the array parameter filters[issues]
$gis->opportunities->filters->skills[0]->id = 10 // set the id of the first element of the array parameter filters[skills]
$gis->opportunities->filters->skills[1]->id = 20 // set the id of the second element of the array parameter filters[skills]
```
<b>Attention:</b> Please take care of the fact that those parameters are saved until you unset them. Even a request will not unset them.

You can call the php function unset on every element in this hierarchy. This will delete the specific instance and thereby all the parameters below. Please remember that when you unset your instance of the GIS class you have to reinitialize the GIS wrapper by yourself. Every object below the GIS class will be reinitialized automatically as soon as you access it.

The easiest way is to unset the endpoint you worked on, after your request. In the example above this would mean `unset($gis->opportunities)`.

### Hash and Array Parameters
When it comes to Hash and Array parameters you should take a closer look at the column data type in the API documentation.

A parameter with the data type Hash does not have an accessable value. Rather you can think of it as the value is consisting in the value of the subparameters.

Thereby an hash parameter is also not accessable as an array in php, rather every subparameter turns into a subobject in php. An example is the parameter filter, used in the example above.

On the other side there are two different kind of array parameters. Those who have subparameters and those who do not have subparameters and thereby just take values. In PHP those parameters turn into arrays.

On the first kind of array parameters you can access the subparameters on each array key, like the filter skills in the example above.

On the second kind of array parameters you can access and set values on each array key. In the example above this would be the filer issues.

### setting many parameters at once
When you want to set many parameters at once without using the long notation with subobjects you can set them as array. Please be aware that this method can be hard to debug.

When you assign an Array to an Endpoint or Parameter the value of each key will be assigned recursively to the sub endpoints and parameter named like the key. This does not work when you have a dynamic part of the path in your array, but as soon as you assign the equivalent array to the last dynamic endpoint it will work.

The example from above would look like below
```
$gis->opportunities = [
    "q" => "some String",
    "filters" => [
        "issues" => [10, 20],
        "skills" => [
            ["id" => 10],
            ["id" => 20]
        ]
    ]
]
```

## Helper functions
- On every object with subobjects you can call the function `exists($name)` to check if a subobject exists. This does not mean that there is already an instance of this object, rather only that it is accessable.
- On Endpoints with Dynamic Sub Endpoints, so those where you can access endpoints via array notation, the `exists($name)` function checks both for subobjects as well as array keys.
    - To only check for subobjects use the function `existsSub($name)`. Subobjects are static path endpoints and parameters.
    - To only check for elements of the dynamic sub endpoint use the function `existsDynamicSub($key)`.
    - When checking for a dynamic sub endpoint the GIS wrapper actually send a request to the GIS to check if the resource is available.
    - It's recommended to use the functions `existsSub($name)` and `existsDynamicSub($key)` on endpoints with dynamic Subs to avoid unnecessary requests
    - Please be aware that the functions `existsSub($name)` and `existsDynamicSub($key)` are only available on objects with dynamic sub objects. On objects with static sub objects and parameters you can only use the function `exists($name)`
- The function `isset($object)`, returns true as soon as the the respective object is initialized. If the object is not initialized it will return false.
    - if `isset($object)` returns false this does not mean that the object is not accessable. To check if an object is accessable use the function `exists($name)`, which is described above
    - at the moment `isset($object)` also return true, when an parameter is initialized, but does not have a value. This might change in the future. For the moment you can use the function `valid($operation)` described below
    - calling `isset($object)` on an array key, will return true when there is an instance at this key. This must not mean that there is a resource existing for this key.
- The function `unset($object)` will delete the instance of the given object.
    - Afterwards `isset($object)` will return false, until you access the object again.
    - Calling `exists($name)` on the parent object will still return true
- The function `count($object)` can be called on paged endpoints and array parameters, both are also iteratable (e.g. in a foreach loop)
    - on paged endpoints this will relate in a request with the current set of parameters
    - to get the number of elements in the last request call `lastCount()` on the endpoint

## Testing
The PHP GIS Wrapper is tested with PHP unit. All the tests can be found in the folder tests. There you can also find the Code Coverage report.

If you send a pull request, please make sure that your code is covered and run phpunit in the root folder before you commit. Normally phpunit will automatically recognize the file phpunit.xml in the root folder.

## Providers
Providers have to purpose to include the functionality of the GIS wrapper in a different context (e.g. a framework).

Currently we support the PHP Framework Lumen and thereby also Laravel. Please check the README in the providers folder, for more details.

# Changelog
## 0.2.5
- added AuthProviderShadow and AuthProviderNationalIdentity
- added ServiceProvider for Lumen (should also work with Laravel)
- added `currentPage()`, `setStartPage($page)` and `setPerPage($number)` function to paged endpoints (tests still missing)
- updated Unit Tests

## 0.2.4
- Fixed some minor bugs in the API Endpoint

## 0.2.3
- Fixed the token regeneration when a GET request runs with an expired token

## 0.2.2
- Fixed an issue in the parameter validation, which occured when parameters of different methods had the same name

## 0.2.1
- improved stability of the swagger parsing

## 0.2
In this version the PHP-GIS-Wrapper was completely refactored. The most important changes are:
- New system architecture, especially for the swagger parser. This leads to cleaner source code and a big performance boost.
- Ability to cache the swagger parsing result, which provides even better performance, especially for big projects
- support the GIS Identity session in all three Authentication Providers, which can improve performance and gives you another set of opportunities
- ArrayAccess for dynamic path parts makes the usage far more intuitive
- Far better support for Array Parameters
- Validation of Parameter types
- the PHP GIS Wrapper became a Composer Package

## 0.1
This was the initial version of the PHP-GIS-Wrapper. It only supported GET requests.
- Originally there was only one AuthProvider called AuthProviderUser
- With the introduction of the GIS v2 this Provider was updated to the new GIS Identity, but then only supported EXPA users
- Later the AuthProviderUser was replaced by AuthProviderEXPA, AuthProviderOP and AuthProviderCombined

# FAQ
If you have any questions, feature wishes, problems or found a bug don't hesitate to send an email to karljohann@familieschubi.de

If you found a bug you can also directly open an issue in the github repository.

If you integrated the PHP GIS Wrapper as Provider or Service in a framework or used it in one of your projects feel free to drop me a message to feature it here, or write a pull request to include your code in the providers folder.

If you wrote another example just send a pull request

