<?php
/**
 * caching.php
 * generate the cache, write it to a file, retrieve the cache from a file and instantiate the GIS wrapper with the cache
 *
 * +++ Please copy the config.example.php to config.php and edit it accordingly
 *
 * @author Karl Johann Schubert
 * @version 0.1
 */
require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/config.php';

/**
 * Generate the full cache and write it to $filename
 */
$filename = __DIR__ . '/cache.dat';

// generate the cache array
$cache = \GISwrapper\GIS::generateFullCache();

// serialize the array to get a string
$cachestring = serialize($cache);

// write it to a file
file_put_contents($filename, $cachestring);

/**
 * Get the full cache from $filename and instantiate the GIS wrapper
 */
$cachestring = file_get_contents($filename);

$cache = unserialize($cachestring);

$user = new \GISwrapper\AuthProviderEXPA(EXPA_USER, EXPA_PW);
$gis = new \GISwrapper\GIS($user, $cache);

/**
 * one-liner
 *
 * generate file: file_put_contents($filename, serialize(\GISwrapper\GIS::generateFullCache()));
 * load from file: $gis = new \GISwrapper\GIS($user, unserialize(file_get_contents($filename)));
 */