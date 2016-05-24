<?php
/**
 * Created by PhpStorm.
 * User: kjs
 * Date: 23.05.16
 * Time: 22:56
 */

namespace GISwrapper;


class APISubFactory
{
    public static function factory($cache, $auth, $pathParams = array()) {
        if($cache['endpoint']) {
            if($cache['paged']) {
                if($cache['dynamicSub']) {
                    return new APIEndpointPagedDynamicSub($cache, $auth, $pathParams);
                } else {
                    return new APIEndpointPaged($cache, $auth, $pathParams);
                }
            } else {
                if($cache['dynamicSub']) {
                    return new APIEndpointDynamicSub($cache, $auth, $pathParams);
                } else {
                    return new APIEndpoint($cache, $auth, $pathParams);
                }
            }
        } else {
            if($cache['dynamicSub']) {
                return new APIDynamicSub($cache, $auth, $pathParams);
            } else {
                return new API($cache, $auth, $pathParams);
            }
        }
    }
}