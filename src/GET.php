<?php
/**
 * Created by PhpStorm.
 * User: kjs
 * Date: 24.05.16
 * Time: 17:31
 */

namespace GISwrapper;


class GET
{
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