<?php
namespace GIS;

require_once( dirname(__FILE__) . '/AuthProvider.php' );

/**
 * Class AuthProviderExpa
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @version 0.1
 * @package GIS
 */
class AuthProviderExpa implements AuthProvider {

    /**
     * @var String
     */
    private $_username;

    /**
     * @var String
     */
    private $_password;

    /**
     * @var String
     */
    private $_token;

    /**
     * @param String $user username of the user
     * @param String $pass password of the user
     */
    function __construct($user, $pass) {
        $this->_username = $user;
        $this->_password = $pass;
    }

    /**
     * @return String
     * @covers \GIS\AuthProviderUser::generateNewToken
     */
    public function getToken() {
        if($this->_token != null && $this->_expires_at > time()) {
            return $this->_token;
        } else {
            return $this->getNewToken();
        }
    }

    /**
     * @return String
     * @throws InvalidAuthResponseException
     * @throws InvalidCredentialsException
     * @covers \GIS\AuthProviderUser::generateNewToken
     */
    public function getNewToken() {
        $this->generateNewToken();

        return $this->_token;
    }

    /**
     * generateNewToken()
     *
     * function that performs a login with GIS auth to get a new access token
     *
     * @return void
     * @throws \GIS\InvalidCredentialsException if the username or password is invalid
     * @throws \GIS\InvalidAuthResponseException if the response does not contain the access token
     */
    private function generateNewToken() {
        $data = "user%5Bemail%5D=" . urlencode($this->_username) . "&user%5Bpassword%5D=" . urlencode($this->_password);

        $req = curl_init('https://auth.aiesec.org/users/sign_in');
        curl_setopt($req, CURLOPT_POST, true);
        curl_setopt($req, CURLOPT_POSTFIELDS, $data);
        curl_setopt($req, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($req, CURLOPT_HEADER, 1);
        curl_setopt($req, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($req, CURLOPT_COOKIEFILE, "");
        $res = curl_exec($req);
        curl_close($req);

        // get token and expiration date from cookies;
        $token = $expire = false;
        preg_match_all('/^Set-Cookie:\s*([^\n]*)/mi', $res, $cookies);
        foreach($cookies[1] as $c) {
            $c = explode('; ', $c);
            foreach($c as $cookie) {
                parse_str($cookie, $cookie);
                if(isset($cookie["expa_token"])) $token = trim($cookie["expa_token"]);
                if(isset($cookie["Expires"])) $expire = @strtotime($cookie["Expires"]);
            }
        }

        if($token !== false && $token !== null) {
            $this->_token = $token;
            if($expire !== false && $expire > 0) {
                $this->_expires_at = $expire;
            } else {
                // if we can not parse the expiration date, assume 1h
                $this->_expires_at = time() + 3600;
            }
        } else  {
            if(strpos($res, "<h2>Invalid email or password.</h2>") !== false) {
                throw new InvalidCredentialsException("Invalid email or password");
            } else {
                throw new InvalidAuthResponseException("The GIS auth response does not match the requirements.");
            }
        }
    }
}