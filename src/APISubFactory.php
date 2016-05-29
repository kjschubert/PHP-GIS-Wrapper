<?php
namespace GISwrapper;

/**
 * Class APISubFactory
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @package GISwrapper
 * @version 0.2
 */
class APISubFactory
{
    /**
     * @param array $cache parsed swagger file for this api
     * @param AuthProvider $auth
     * @param array $pathParams array with values for dynamic parts of the path
     * @return API|APIDynamicSub|APIEndpoint|APIEndpointDynamicSub|APIEndpointPaged|APIEndpointPagedDynamicSub
     */
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