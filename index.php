<?php
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