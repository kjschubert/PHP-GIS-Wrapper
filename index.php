<?php
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