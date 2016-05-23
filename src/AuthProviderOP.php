<?php
namespace GIS;

require_once( dirname(__FILE__) . '/AuthProvider.php' );

/**
 * Class AuthProviderOP
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @version 0.2
 * @package GISwrapper
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
     * @var bool
     */
    private $_verifyPeer = true;

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

        $res = null;
        $req = curl_init();
        curl_setopt($req, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($req, CURLOPT_HEADER, 1);
        curl_setopt($req, CURLOPT_SSL_VERIFYPEER, $this->_verifyPeer);
        curl_setopt($req, CURLOPT_COOKIEFILE, $this->_session);
        curl_setopt($req, CURLOPT_COOKIEJAR, $this->_session);

        if($this->_session == "" || !file_exists($this->_session)) {
            // if no session exists, initiate gis identity session as if the request would come from OP, so don't follow redirects to save time
            curl_setopt($req, CURLOPT_FOLLOWLOCATION, false);

            // run the request up to three times if it fails
            $attempts = 0;
            $success = false;
            while(!$success && $attempts < 3) {
                curl_setopt($req, CURLOPT_URL, 'https://auth.aiesec.org/oauth/authorize?redirect_uri=https%3A%2F%2Fopportunities.aiesec.org%2Fauth&response_type=code&client_id=43e49fdc8581a196cafa9122c2e102aa22df72a0e8aa6d5a73f27afa56fe8890');
                if(curl_exec($req) !== false) {
                    $success = true;
                }
                $attempts++;
            }

            if($success) {
                // if successful, perform login on gis identity while keeping session cookie, therefore we need to follow redirects
                curl_setopt($req, CURLOPT_FOLLOWLOCATION, true);

                // run the request up to three times if it fails
                $attempts = 0;
                $success = false;
                while(!$success && $attempts < 3) {
                    curl_setopt($req, CURLOPT_URL, 'https://auth.aiesec.org/users/sign_in');
                    curl_setopt($req, CURLOPT_POST, true);
                    curl_setopt($req, CURLOPT_POSTFIELDS, $data);
                    $res = curl_exec($req);

                    // check if the request was successful
                    if($res !== false) $success = true;
                    $attempts++;
                }

                if($success) {
                    proceed($res);
                } else {
                    throw new InvalidAuthResponseException("Could not login.");
                }
            } else {
                throw new InvalidAuthResponseException("Could not prepare login to OP.");
            }
        } else {
            // if we have a session, try to generate a token
            curl_setopt($req, CURLOPT_URL, 'https://auth.aiesec.org/oauth/authorize?redirect_uri=https%3A%2F%2Fopportunities.aiesec.org%2Fauth&response_type=code&client_id=43e49fdc8581a196cafa9122c2e102aa22df72a0e8aa6d5a73f27afa56fe8890');
            curl_setopt($req, CURLOPT_FOLLOWLOCATION, true);

            // run the request up to three times if it fails
            $attempts = 0;
            $success = false;
            while(!$success && $attempts < 3) {
                // run request
                $res = curl_exec($req);

                if($res !== false) {
                    // if successful, check for token
                    try {
                        proceed($res);
                        $success = true;
                    } catch (InvalidAuthResponseException $e) {
                        // if there was no token, this can mean that the session was invalid, thereby send the login credentials
                        curl_setopt($req, CURLOPT_URL, 'https://auth.aiesec.org/users/sign_in');
                        curl_setopt($req, CURLOPT_POST, true);
                        curl_setopt($req, CURLOPT_POSTFIELDS, $data);
                    }
                } else {
                    // retry if it fails
                    curl_setopt($req, CURLOPT_URL, 'https://auth.aiesec.org/oauth/authorize?redirect_uri=https%3A%2F%2Fopportunities.aiesec.org%2Fauth&response_type=code&client_id=43e49fdc8581a196cafa9122c2e102aa22df72a0e8aa6d5a73f27afa56fe8890');
                }
                $attempts++;
            }
        }

        function proceed($res) {
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
}