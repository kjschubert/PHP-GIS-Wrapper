<?php
namespace GISwrapper;

/**
 * Class AuthProviderExpa
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @version 0.2
 * @package GISwrapper
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
     * @var timestamp
     */
    private $_expires_at;

    /**
     * @var bool
     */
    private $_verifyPeer = true;

    /**
     * @var String
     */
    private $_session = "";

    /**
     * @param String $user username of the user
     * @param String $pass password of the user
     * @throws \Exception if curl is missing
     * @param bool $SSLVerifyPeer set curl option verify ssl peer (default true)
     */
    function __construct($user, $pass, $SSLVerifyPeer = true) {
        // check for curl
        if(function_exists('curl_init')) {
            $this->_username = $user;
            $this->_password = $pass;
            $this->_verifyPeer = $SSLVerifyPeer;
        } else {
            throw new \Exception("cURL missing");
        }
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
     * @return timestamp
     */
    public function getExpiresAt() {
        return $this->_expires_at;
    }

    /**
     * @return String session filename
     */
    public function getSession() {
        return $this->_session;
    }

    /**
     * @param $session session filename
     */
    public function setSession($session) {
        $this->_session = strval($session);
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

        if($this->_session == "" || !file_exists($this->_session)) {
            $req = curl_init('https://auth.aiesec.org/users/sign_in');
            curl_setopt($req, CURLOPT_POST, true);
            curl_setopt($req, CURLOPT_POSTFIELDS, $data);
        } else {
            $req = curl_init('https://auth.aiesec.org/oauth/authorize?redirect_uri=https%3A%2F%2Fexperience.aiesec.org%2Fsign_in&response_type=code&client_id=349321fd15814e9fdd2c5abe062a6fb10a27a95dd226fce287adb6c51d3de3df');
        }
        curl_setopt($req, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($req, CURLOPT_HEADER, 1);
        curl_setopt($req, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($req, CURLOPT_SSL_VERIFYPEER, $this->_verifyPeer);
        curl_setopt($req, CURLOPT_COOKIEFILE, $this->_session);
        curl_setopt($req, CURLOPT_COOKIEJAR, $this->_session);

        // get token and expiration date from cookies;
        $attempts = 0;
        $token = $expire = false;
        while(!$token && $attempts < 3) {
            $res = curl_exec($req);
            preg_match_all('/^Set-Cookie:\s*([^\n]*)/mi', $res, $cookies);
            foreach($cookies[1] as $c) {
                $c = explode('; ', $c);
                foreach($c as $cookie) {
                    parse_str($cookie, $cookie);
                    if(isset($cookie["expa_token"])) $token = trim($cookie["expa_token"]);
                    if(isset($cookie["Expires"])) $expire = @strtotime($cookie["Expires"]);
                }
            }
            $attempts++;

            // if we didn't got a token assume that the session is invalid and change the request to login
            if(!$token) {
                curl_setopt($req, CURLOPT_URL, 'https://auth.aiesec.org/users/sign_in');
                curl_setopt($req, CURLOPT_POST, true);
                curl_setopt($req, CURLOPT_POSTFIELDS, $data);
            }
        }

        if($token != false) {
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