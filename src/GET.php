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
        // init curl request
        $req = curl_init();
        curl_setopt($req, CURLOPT_RETURNTRANSFER, true);

        $attempts = 0;
        $res = false;
        while($res === false && $attempts < 3) {
            curl_setopt($req, CURLOPT_URL, $url . 'access_token=' . $auth->getToken());
            $res = curl_exec($req);
            if(curl_getinfo($req, CURLINFO_HTTP_CODE) == 401 && $attempts < 2) {
                $auth->getNewToken();
                $res = false;
            } elseif($res) {
                $res = json_decode($res);
                if($res === null) $res = false;
            }
            $attempts++;
        }

        // validate response
        if($res === false) {
            throw new NoResponseException("Could not load endpoint " . $url);
        } elseif($res == null) {
            echo curl_getinfo($req, CURLOPT_URL);
            throw new InvalidAPIResponseException("Invalid Response on " . $url);
        } else {
            return $res;
        }
    }
}