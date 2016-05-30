<?php
/**
 * opportunity.php
 * get a random branch to create, update and delete a opportunity
 *
 * +++ Please copy the config.example.php to config.php and edit it accordingly
 *
 * @author Karl Johann Schubert
 * @version 0.1
 */

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/config.php';

// instantiate auth provider
$user = new \GISwrapper\AuthProviderEXPA(EXPA_USER, EXPA_PW);

// instantiate GIS wrapper
$gis = new \GISwrapper\GIS($user);

/**
 * get the current user
 */
$current_person = $gis->current_person->get();

// save the current office id
$office = $current_person->current_offices[0]->id;

// output the office id
echo "Office ID: " . $office . PHP_EOL;

/**
 * get the first branch from a organisation of the current office so we keep edit rights on the opportunity
 */
$gis->organisations->filters->committee = $office;
$branch = null;
foreach($gis->organisations as $org) {
    if($branch == null) {
        $branches = $gis->organisations[$org->id]->branches->get()->data;
        if(count($branches) > 0) {
            $branch = $branches[0]->id;
        } else {
            echo "Organisation " . $org->name . " does not have a branch";
        }
    } else {
        break;
    }
}

// check that we got a branch
if($branch == null) {
    die("Could not get a branch");
}

// output the branch id
echo "Branch ID: " . $branch . PHP_EOL;

/**
 * create a new opportunity
 */
$gis->opportunities->opportunity->title = "Test Opportunity";    // set the title
$gis->opportunities->opportunity->programme_id = 1;  // set the programme to GCDP
$gis->opportunities->opportunity->branch_id = strval($branch);  // set the branch id (convert it to string, because the parameter data type is string)
$gis->opportunities->opportunity->host_lc_id = strval($current_person->person->home_lc->id);    // set host lc to home lc of current person (convert it to string, because the parameter data type is string)

// create the opportunity and save it to $o
$o = $gis->opportunities->create();

// save the opportunity id
$opportunity = $o->id;

// unset the endpoint to clear all parameters. This is not needed here, but a good case practice
unset($gis->opportunities);

// output the opportunity id
echo "Opportunity ID: " . $opportunity . PHP_EOL;

/**
 * update the EP managers so that the current user is the only one.
 *
 * Pay attention to the fact, that at some endpoints there are not endpoints for a specific action like in this case adding an EP manager.
 * We can only override the attribute managers, whereby we need to make sure that the value we set, contains also all persons which should stay EP manager.
 * This is like as we implement the function to add a EP manager by ourselves
 */
$gis->opportunities[$opportunity]->opportunity->manager_ids[] = $current_person->person->id;

// send the update request and save the updated opportunity to $o
$o = $gis->opportunities[$opportunity]->update();

// output the managers of the opportunity
foreach($o->managers as $manager) {
    echo "Manager: " . $manager->full_name . PHP_EOL;
}

/**
 * Delete the opportunity
 */
$res = $gis->opportunities[$opportunity]->delete();
if($res === true) {
    echo "opportunity successful deleted" . PHP_EOL;
} else {
    echo "could not delete opportunity" . PHP_EOL;
}
