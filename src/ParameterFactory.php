<?php
/**
 * Created by PhpStorm.
 * User: kjs
 * Date: 24.05.16
 * Time: 19:20
 */

namespace GISwrapper;


class ParameterFactory
{
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