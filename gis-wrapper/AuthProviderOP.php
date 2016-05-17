<?php
namespace GIS;

require_once( dirname(__FILE__) . '/AuthProvider.php' );

/**
 * Class AuthProviderOP
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @version 0.1
 * @package GIS
 */
class AuthProviderOP implements AuthProvider {

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
     * @var timestamp
     */
    private $_expires_at;

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
     * @return String access token
     * @throws \GIS\InvalidCredentialsException if the username or password is invalid
     * @throws \GIS\InvalidAuthResponseException if the response does not contain the access token
     */
    private function generateNewToken() {
        $data = "user%5Bemail%5D=" . urlencode($this->_username) . "&user%5Bpassword%5D=" . urlencode($this->_password);

        // initiate gis identity session as request would come from OP
        $req = curl_init('https://auth.aiesec.org/oauth/authorize?redirect_uri=https%3A%2F%2Fopportunities.aiesec.org%2Fauth&response_type=code&client_id=43e49fdc8581a196cafa9122c2e102aa22df72a0e8aa6d5a73f27afa56fe8890');
        curl_setopt($req, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($req, CURLOPT_HEADER, 1);
        curl_setopt($req, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($req, CURLOPT_COOKIEFILE, "");
        curl_exec($req);

        // perform login on gis identity while keeping session cookie
        curl_setopt($req, CURLOPT_URL, 'https://auth.aiesec.org/users/sign_in');
        curl_setopt($req, CURLOPT_POST, true);
        curl_setopt($req, CURLOPT_POSTFIELDS, $data);
        curl_setopt($req, CURLOPT_FOLLOWLOCATION, true);
        $res = curl_exec($req);
        curl_close($req);

        // get token cookie;
        $token = $expire = false;
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $res, $cookies);
        foreach($cookies[1] as $c) {
            parse_str($c, $cookie);
            if(isset($cookie["aiesec_token"])) {
                $content = json_decode($cookie["aiesec_token"]);
                $token = $content->token->access_token;
                $expire = @strtotime($content->token->expires_at);
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