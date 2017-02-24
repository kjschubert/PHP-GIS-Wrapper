<?php
namespace GISwrapper;

/**
 * Class AuthProviderCombined
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @version 0.2
 * @package GISwrapper
 */
class AuthProviderCombined implements AuthProvider {

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
     * @var int timestamp
     */
    private $_expires_at;

    /**
     * @var Object
     */
    private $_currentPerson;

    /**
     * @var bool
     */
    protected $_type;

    /**
     * @var bool
     */
    private $_verifyPeer = true;

    /**
     * @var String
     */
    private $_session = "";

    /**
     * @param String $user username of the user or session file path
     * @param String|null $pass password of the user | null for session
     * @param bool $SSLVerifyPeer optional set curl option verify ssl peer (default true)
     * @throws \Exception if curl is missing
     */
    function __construct($user, $pass = null, $SSLVerifyPeer = true) {
        // check for curl
        if(function_exists('curl_init')) {
            if($pass === null) {    // got a session
                $this->_username = "";
                $this->_password = "";
                $this->_session = $user;
                if(!file_exists($this->_session)) {
                    trigger_error("Session file does not exists.", E_USER_ERROR);
                }
            } else {    //got credentials
                $this->_username = $user;
                $this->_password = $pass;
            }
            $this->_verifyPeer = $SSLVerifyPeer;
        } else {
            throw new \Exception("cURL missing");
        }
    }

    /**
     * @return String
     * @throws InvalidAuthResponseException
     * @throws InvalidCredentialsException
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
     */
    public function getNewToken() {
        $this->generateNewToken();

        return $this->_token;
    }

    /**
     * @return int timestamp
     */
    public function getExpiresAt() {
        return $this->_expires_at;
    }

    /**
     * @return Object
     */
    public function getCurrentPerson() {
        if(!$this->_currentPerson){
            $this->requestCurrentPerson();
        }
        return $this->_currentPerson;
    }

    /**
     * @return String
     */
    public function getType() {
        return ($this->_type) ? 'EXPA' : 'OP';
    }

    /**
     * @return bool
     */
    public function isEXPA() {
        return $this->_type;
    }

    /**
     * @return bool
     */
    public function isOP() {
        return !$this->_type;
    }

    /**
     * @return String session filename
     */
    public function getSession() {
        return $this->_session;
    }

    /**
     * @param $session String session filename
     */
    public function setSession($session) {
        $this->_session = strval($session);
    }

    /**
     * generateNewToken()
     *
     * function that performs a login with GIS auth to get a new access token
     *
     * @throws InvalidCredentialsException if the username or password is invalid
     */
    protected function generateNewToken() {
        // first assume that the type is EXPA
        $this->_type = true;

        // run the GIS Auth Flow for EXPA
        $this->GISauthFlow('https://auth.aiesec.org/oauth/authorize?redirect_uri=https%3A%2F%2Fexperience.aiesec.org%2Fsign_in&response_type=code&client_id=349321fd15814e9fdd2c5abe062a6fb10a27a95dd226fce287adb6c51d3de3df');

        // if the token is not valid
        if(!$this->getCurrentPerson()) {
            // assume type is OP
            $this->_type = false;

            // run the GIS Auth Flow for OP
            $this->GISauthFlow('https://auth.aiesec.org/oauth/authorize?redirect_uri=https%3A%2F%2Fopportunities.aiesec.org%2Fauth&response_type=code&client_id=43e49fdc8581a196cafa9122c2e102aa22df72a0e8aa6d5a73f27afa56fe8890');

            // if token is still not valid throw a exception
            if(!$this->getCurrentPerson()) {
                throw new InvalidCredentialsException('Could not get a valid token');
            }
        }
    }

    /**
     * GISauthFlow
     *
     * template to perform a login against one of the GIS interfaces
     *
     * @param $url string interface specific login url
     * @return bool
     * @throws InvalidAuthResponseException
     */
    protected function GISauthFlow($url) {
        // prepare curl request
        $req = curl_init();
        curl_setopt($req, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($req, CURLOPT_HEADER, 1);
        curl_setopt($req, CURLOPT_SSL_VERIFYPEER, $this->_verifyPeer);
        curl_setopt($req, CURLOPT_COOKIEFILE, $this->_session);
        curl_setopt($req, CURLOPT_COOKIEJAR, $this->_session);
        curl_setopt($req, CURLOPT_FOLLOWLOCATION, true);

        // first check if we are working with an existing session, because if this fails we either need to run a normal login flow
        if(file_exists($this->_session)) {
            $res = true;
            for($i = 0; $i < 3; $i++) {
                // set url
                curl_setopt($req, CURLOPT_URL, $url);

                // run request
                $res = curl_exec($req);

                // check response
                if($res !== false) {
                    try {
                        // if successful, check for token
                        $this->proceedToken($res);

                        // if we are still here there was no exception so we got a token
                        curl_close($req);
                        return true;
                    } catch (InvalidAuthResponseException $e) {
                        // if there was no token, this can mean that the session was invalid, thereby break the loop and skip to normal login procedure
                        break;
                    }
                }
            }
        }

        // if we are still here we need to run a normal login, therefore we need a CSRF token first
        $data = false;
        for($i = 0; $i < 3; $i++) {
            curl_setopt($req, CURLOPT_URL, $url);
            $res = curl_exec($req);
            if($res !== false) {
                // try to find csrf token in response
                preg_match( '/<meta.*content="(.*)".*name="csrf-token"/', $res, $match );

                // check for result
                if(isset($match[1])) {
                    // prepare post data if we got a token and skip further loop runs
                    $data = "user%5Bemail%5D=" . urlencode($this->_username) . "&user%5Bpassword%5D=" . urlencode($this->_password) . '&authenticity_token=' . urlencode($match[1]);
                    break;
                }
            }
        }

        // throw exception if we did not get a csrf token
        if($data === false) {
            curl_close($req);
            throw new InvalidAuthResponseException("Did not get a CSRF token.");
        }

        // through we ran the csrf request against the specific url our session is initiated and we can just perform an login
        for($i = 0; $i < 3; $i++) {
            // update the curl handle
            curl_setopt($req, CURLOPT_URL, 'https://auth.aiesec.org/users/sign_in');
            curl_setopt($req, CURLOPT_POST, true);
            curl_setopt($req, CURLOPT_POSTFIELDS, $data);

            // run request
            $res = curl_exec($req);

            // check response
            if($res !== false) {
                // check for token
                $this->proceedToken($res);

                // if we are still here we succeeded
                curl_close($req);
                return true;
            }
        }

        // close curl handle
        curl_close($req);

        // if we are still here we have to throw an exception
        throw new InvalidAuthResponseException("Could not login.");
    }

    /**
     * proceedToken
     *
     * proceeds an gis auth response and extracts the token
     *
     * @param $res string GIS auth response
     * @throws InvalidAuthResponseException
     * @throws InvalidCredentialsException
     */
    private function proceedToken($res) {
        // get token and expiration date from cookie
        $token = $expire = false;
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $res, $cookies);
        foreach($cookies[1] as $c) {
            parse_str($c, $cookie);
            if(isset($cookie["aiesec_token"])) {
                $content = json_decode($cookie["aiesec_token"]);
                $token = $content->token->access_token;
                $expire = @strtotime($content->token->expires_at);
            } elseif(isset($cookie["expa_token"])) {
                $token = $cookie["expa_token"];
            } elseif(isset($cookie['Expires'])); {
                $expires = @strtotime($cookie['Expires']);
            }
        }

        // check if we got a token
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

    /**
     * requestCurrentPerson
     *
     * check if token is valid by requesting the current person object
     *
     * @return bool
     * @throws InvalidAPIResponseException
     */
    private function requestCurrentPerson(){
        // prepare curl request
        $req = curl_init();
        curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($req, CURLOPT_SSL_VERIFYPEER, $this->_verifyPeer);

        // try to get current person to check if token is valid
        for($i = 0; $i < 3; $i++) {
            // run request
            curl_setopt($req, CURLOPT_URL, 'https://gis-api.aiesec.org/v2/current_person.json?access_token=' . $this->getToken());
            $res = curl_exec($req);

            // check response
            if($res !== false) {
                // decode response
                $currentPerson = json_decode($res);

                // check content
                if(isset($currentPerson->person)) {
                    curl_close($req);
                    $this->_currentPerson = $currentPerson;
                    return true;
                } elseif($currentPerson !== null) {
                    // if we got a valid json object back, but without a person the token is invalid
                    curl_close($req);
                    return false;
                }
            }
        }

        // close curl handle
        curl_close($req);

        // throw exception if we did not get a result
        throw new InvalidAPIResponseException("Could not load current person after 3 attempts.");
    }

}