<?php
namespace GISwrapper;

/**
 * Class ParameterFactory
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @package GISwrapper
 * @version 0.2
 */
class ParameterFactory
{
    /**
     * @param array $cache parsed swagger file for this parameter
     * @return ParameterArrayType|ParameterDefaultType
     */
    public static function factory($cache) {
        $array = false;
        $strict = true;
        foreach($cache['operations'] as $o) {
            if($o['type'] == "Array") {
                $array = true;
                break;
            } else {
                $strict = false;
            }
        }
        if($array) {
            return new ParameterArrayType($cache, $strict);
        } else {
            return new ParameterDefaultType($cache);
        }
    }
}