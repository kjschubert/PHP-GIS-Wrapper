<?php
// require the Authentication Provider vor GIS Auth login credentials and the GIS wrapper main class
require_once( dirname(__FILE__) . '/../gis-wrapper/AuthProviderUser.php');
require_once( dirname(__FILE__) . '/../gis-wrapper/GIS.php');

date_default_timezone_set('Europe/Berlin');

define("API_USER", "itguy@aiesec.net");
define("API_PASS", "ITguysKnowThatTheyNeedSuperSecretPasswords!!!11!");