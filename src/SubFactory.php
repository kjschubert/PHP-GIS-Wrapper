<?php
/**
 * Created by PhpStorm.
 * User: kjs
 * Date: 23.05.16
 * Time: 22:56
 */

namespace GISwrapper;


class SubFactory
{
    public static function factory($cache, $auth, $pathParams = array()) {
        if($cache['endpoint']) {
            return new Endpoint($cache, $auth, $pathParams);
        } else {
            return new API($cache, $auth, $pathParams);
        }
    }
}