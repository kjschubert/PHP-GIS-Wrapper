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
    private $_type;

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
     * @throws InvalidAPIResponseException if current person endpoint respond invalid data
     * @throws InvalidAuthResponseException if the response does not contain the access token
     * @throws InvalidCredentialsException if the username or password is invalid
     */
    private function generateNewToken() {
        // assume type is EXPA
        $this->_type = true;
        
        // prepare data array
        $data = "user%5Bemail%5D=" . urlencode($this->_username) . "&user%5Bpassword%5D=" . urlencode($this->_password);

        // prepare curl request
        if($this->_session == "" || !file_exists($this->_session)) {    //login for new session
            $req = curl_init('https://auth.aiesec.org/users/sign_in');
            curl_setopt($req, CURLOPT_RETURNTRANSFER, 1);
            preg_match( '/<meta.*content="(.*)".*name="csrf-token"/', curl_exec($req), $match );
            $data .= "&authenticity_token=" . urlencode($match[1]);
            
            $req = curl_init('https://auth.aiesec.org/users/sign_in');
            curl_setopt($req, CURLOPT_POST, true);
            curl_setopt($req, CURLOPT_POSTFIELDS, $data);
        } else {    // request EXPA token for existing session
            $req = curl_init('https://auth.aiesec.org/oauth/authorize?redirect_uri=https%3A%2F%2Fexperience.aiesec.org%2Fsign_in&response_type=code&client_id=349321fd15814e9fdd2c5abe062a6fb10a27a95dd226fce287adb6c51d3de3df');
        }
        
        //set curl parameters
        curl_setopt($req, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($req, CURLOPT_HEADER, 1);
        curl_setopt($req, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($req, CURLOPT_SSL_VERIFYPEER, $this->_verifyPeer);
        curl_setopt($req, CURLOPT_COOKIEFILE, $this->_session);
        curl_setopt($req, CURLOPT_COOKIEJAR, $this->_session);

        // get token and expiration date from cookies, retry request up to three times
        $attempts = 0;
        $token = $expire = $res = false;
        while(!$res && $attempts < 3) {
            // send request
            $res = curl_exec($req);

            // check if request was successful
            if($res !== false) {
                // get data from cookies
                preg_match_all('/^Set-Cookie:\s*([^\n]*)/mi', $res, $cookies);
                foreach($cookies[1] as $c) {
                    $c = explode('; ', $c);
                    foreach($c as $cookie) {
                        parse_str($cookie, $cookie);
                        if(isset($cookie["expa_token"])) $token = trim($cookie["expa_token"]);
                        if(isset($cookie["Expires"])) $expire = @strtotime($cookie["Expires"]);
                    }
                }

                // if we didn't got a token and the credentials are not invalid, assume that the session is invalid and change the request to login
                if(!$token && strpos($res, "<h2>Invalid email or password.</h2>") === false) {
                    curl_setopt($req, CURLOPT_URL, 'https://auth.aiesec.org/users/sign_in');
                    curl_setopt($req, CURLOPT_POST, true);
                    curl_setopt($req, CURLOPT_POSTFIELDS, $data);
                    $res = false;
                }
            }
            $attempts++;
        }

        if($token != false) {
            // prepare another curl request
            $req2 = curl_init();
            curl_setopt($req2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($req2, CURLOPT_SSL_VERIFYPEER, $this->_verifyPeer);

            // try to get current person to check if token is valid
            $attempts = 0;
            $current_person = false;
            while(!$current_person && $attempts < 3) {
                // run request
                curl_setopt($req2, CURLOPT_URL, 'https://gis-api.aiesec.org/v2/current_person.json?access_token=' . $token);
                $current_person = json_decode(curl_exec($req2));

                // check response
                if($current_person == null) {
                    $current_person = false;
                }
                $attempts++;
            }

            // check if we got the current person
            if($current_person === false) {
                throw new InvalidAPIResponseException("Could not load current person after $attempts attempts.");
            } else if(isset($current_person->status)) {
                // if the endpoint returns, that an active role is needed, request OP token
                if($current_person->status->code == 403 && $current_person->status->message == "Active role required to view this content.") {
                    // set type to OP
                    $this->_type = false;

                    // try login request to OP up to three times
                    $attempts = 0;
                    $content = null;
                    while($content == null && $attempts < 3) {
                        // send request
                        curl_setopt($req, CURLOPT_URL, 'https://auth.aiesec.org/oauth/authorize?redirect_uri=https%3A%2F%2Fopportunities.aiesec.org%2Fauth&response_type=code&client_id=43e49fdc8581a196cafa9122c2e102aa22df72a0e8aa6d5a73f27afa56fe8890');
                        $res = curl_exec($req);

                        // get cookie
                        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $res, $cookies);
                        foreach($cookies[1] as $c) {
                            parse_str($c, $cookie);
                            if(isset($cookie["aiesec_token"])) {
                                $content = json_decode($cookie["aiesec_token"]);
                            }
                        }
                        $attempts++;
                    }
                    curl_close($req);

                    // check cookie content
                    if($content !== null) {
                        $this->_token = $content->token->access_token;
                        $expire = @strtotime($content->token->expires_at);
                        if($expire !== false && $expire > 0) {
                            $this->_expires_at = $expire;
                        } else {
                            // if we can not parse the expiration date, assume 1h
                            $this->_expires_at = time() + 3600;
                        }

                        // try to get current person up to three times
                        $attempts = 0;
                        $this->_currentPerson = false;
                        while(!$this->_currentPerson && $attempts < 3) {
                            // send request
                            curl_setopt($req2, CURLOPT_URL, 'https://gis-api.aiesec.org/v2/current_person.json?access_token=' . $this->_token);
                            $this->_currentPerson = json_decode(curl_exec($req2));

                            // check response
                            if($this->_currentPerson == null) {
                                $this->_currentPerson = false;
                            }
                            $attempts++;
                        }

                        // check response content
                        if(!isset($this->_currentPerson->person)) {
                            if(isset($this->_currentPerson->status)) {
                                throw new InvalidAPIResponseException($this->_currentPerson->status->message);
                            } else {
                                throw new InvalidAPIResponseException("Current Person Endpoint does not contain a person object ($attempts Attempts)");
                            }
                        }
                    } else {
                        curl_close($req2);
                        throw new InvalidAuthResponseException("The GIS auth response does not match the requirements. ($attempts Attempts)");
                    }
                } else {
                    curl_close($req);
                    curl_close($req2);
                    throw new InvalidAPIResponseException($res->status->message);
                }
            } elseif(isset($current_person->person)) {  // validated EXPA token
                curl_close($req);
                curl_close($req2);

                $this->_currentPerson = $current_person;
                $this->_token = $token;

                if($expire !== false && $expire > 0) {
                    $this->_expires_at = $expire;
                } else {
                    // if we can not parse the expiration date, assume 1h
                    $this->_expires_at = time() + 3600;
                }
            } else {
                curl_close($req);
                curl_close($req2);

                throw new InvalidAPIResponseException("Response for current person does not contain a person. ($attempts Attempts)");
            }
        } else  {
            curl_close($req);

            if(strpos($res, "<h2>Invalid email or password.</h2>") !== false) {
                throw new InvalidCredentialsException("Invalid email or password");
            } else {
                throw new InvalidAuthResponseException("The GIS auth response does not match the requirements. ($attempts Attempts)");
            }
        }
    }
}