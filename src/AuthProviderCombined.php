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
     * @throws InvalidAPIResponseException if current person endpoint respond invalid data
     * @throws InvalidAuthResponseException if the response does not contain the access token
     * @throws InvalidCredentialsException if the username or password is invalid
     */
    private function generateNewToken() {
        // assume type is EXPA
        $this->_type = true;
        $auth = new AuthProviderEXPA($this->_username, $this->_password, $this->_verifyPeer);
        $this->_token = $auth->getToken();
        $this->_expires_at = $auth->getExpiresAt();
        try {
            $current_person = $this->getCurrentPerson();
        } catch (InvalidAPIResponseException $e){
            // if no person object, assume it is an OP account
            $this->_type = false;
            $auth = new AuthProviderOP($this->_username, $this->_password, $this->_verifyPeer);
            $this->_token = $auth->getToken();
            $this->_expires_at = $auth->getExpiresAt();
            try {
                $this->requestCurrentPerson();
            } catch (InvalidAPIResponseException $e){
                throw new InvalidAPIResponseException("Could not validate Account.");
            }
        }
    }

    private function requestCurrentPerson(){
        // prepare curl request
        $req = curl_init();
        curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($req, CURLOPT_SSL_VERIFYPEER, $this->_verifyPeer);
        // try to get current person to check if token is valid
        $attempts = 0;
        $current_person = false;
        while(!$current_person && $attempts < 3) {
            // run request
            curl_setopt($req, CURLOPT_URL, 'https://gis-api.aiesec.org/v2/current_person.json?access_token=' . $this->getToken());
            $current_person = json_decode(curl_exec($req));

            // check response
            if($current_person == null) {
                $current_person = false;
            }
            $attempts++;
        }
        if($current_person !== false && isset($current_person->person)) {
            curl_close($req);
            $this->_currentPerson = $current_person;
        } else {
            curl_close($req);
            if(isset($currentPerson->status)) {
                throw new InvalidAPIResponseException($currentPerson->status->message);
            } else {
                throw new InvalidAPIResponseException("Could not load current person after $attempts attempts.");
            }
        }
    }

}