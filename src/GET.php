<?php
namespace GISwrapper;

/**
 * Class GET
 * helper class for GET requests
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @package GISwrapper
 * @version 0.2
 */
class GET
{
    /**
     * @param string $url
     * @param AuthProvider $auth
     * @return object representing the requested resource
     * @throws InvalidAPIResponseException
     * @throws NoResponseException
     */
    public static function request($url, $auth) {
        $attempts = 0;
        $res = false;
        while(!$res && $attempts < 3) {
            $res = @file_get_contents($url . 'access_token=' . $auth->getToken());
            if($res !== false) {
                $res = json_decode($res);
                if($res !== null) {
                    if(isset($res->status)) {
                        if($res->status->code == '401' && $attempts < 2) {
                            $res = false;
                            $auth->getNewToken();
                        }
                    }
                } else {
                    $res = false;
                }
            }
            $attempts++;
        }
        // validate response
        if($res === false) {
            throw new NoResponseException("Could not load endpoint " . $url);
        } elseif($res == null) {
            throw new InvalidAPIResponseException("Invalid Response on " . $url);
        } else {
            return $res;
        }
    }
}