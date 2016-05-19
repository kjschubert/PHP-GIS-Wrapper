<?php
namespace GIS;

require_once( dirname(__FILE__) . '/AuthProvider.php' );

/**
 * Class AuthProviderCombined
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @version 0.1
 * @package GIS
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
     * @var timestamp
     */
    private $_expires_at;

    /**
     * @var String
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
     * @param String $user username of the user
     * @param String $pass password of the user
     * @param bool $SSLVerifyPeer set curl option verify ssl peer (default true)
     */
    function __construct($user, $pass, $SSLVerifyPeer = true) {
        $this->_username = $user;
        $this->_password = $pass;
        $this->_verifyPeer = $SSLVerifyPeer;
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
     * @return date
     */
    public function getExpiresAt() {
        return $this->_expires_at;
    }

    /**
     * @return String
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
     * generateNewToken()
     *
     * function that performs a login with GIS auth to get a new access token
     *
     * @throws InvalidAPIResponseException if current person endpoint respond invalid data
     * @throws InvalidAuthResponseException if the response does not contain the access token
     * @throws InvalidCredentialsException if the username or password is invalid
     */
    private function generateNewToken() {
        $this->_type = true;
        $data = "user%5Bemail%5D=" . urlencode($this->_username) . "&user%5Bpassword%5D=" . urlencode($this->_password);

        $req = curl_init('https://auth.aiesec.org/users/sign_in');
        curl_setopt($req, CURLOPT_POST, true);
        curl_setopt($req, CURLOPT_POSTFIELDS, $data);
        curl_setopt($req, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($req, CURLOPT_HEADER, 1);
        curl_setopt($req, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($req, CURLOPT_COOKIEFILE, "");
        curl_setopt($req, CURLOPT_SSL_VERIFYPEER, $this->_verifyPeer);

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
        }

        if($token != false) {
            // try to get current person to check if token is valid
            $req2 = curl_init('https://gis-api.aiesec.org/v2/current_person.json?access_token=' . $token);
            curl_setopt($req2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($req2, CURLOPT_SSL_VERIFYPEER, $this->_verifyPeer);
            $attempts = 0;
            $current_person = false;
            while(!$current_person && $attempts < 3) {
                $current_person = json_decode(curl_exec($req2));
                if($current_person == null) {
                    $current_person = false;
                    $attempts++;
                }
            }

            if($current_person === false) {
                throw new InvalidAPIResponseException("Could not load current person.");
            } else if(isset($current_person->status)) {
                if($current_person->status->code == 403 && $current_person->status->message == "Active role required to view this content.") {
                    $this->_type = false;

                    curl_setopt($req, CURLOPT_URL, 'https://auth.aiesec.org/oauth/authorize?redirect_uri=https%3A%2F%2Fopportunities.aiesec.org%2Fauth&response_type=code&client_id=43e49fdc8581a196cafa9122c2e102aa22df72a0e8aa6d5a73f27afa56fe8890');
                    $attempts = 0;
                    $content = null;
                    while($content == null && $attempts < 3) {
                        $res = curl_exec($req);
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

                    if($content !== null) {
                        $this->_token = $content->token->access_token;
                        $expire = @strtotime($content->token->expires_at);
                        if($expire !== false && $expire > 0) {
                            $this->_expires_at = $expire;
                        } else {
                            // if we can not parse the expiration date, assume 1h
                            $this->_expires_at = time() + 3600;
                        }

                        curl_setopt($req2, CURLOPT_URL, 'https://gis-api.aiesec.org/v2/current_person.json?access_token=' . $this->_token);
                        $attempts = 0;
                        $this->_currentPerson = false;
                        while(!$this->_currentPerson && $attempts < 3) {
                            $this->_currentPerson = json_decode(curl_exec($req2));
                            if($this->_currentPerson == null) {
                                $this->_currentPerson = false;
                                $attempts++;
                            }
                        }

                        if(!isset($this->_currentPerson->person)) {
                            if(isset($this->_currentPerson->status)) {
                                throw new InvalidAPIResponseException($this->_currentPerson->status->message);
                            } else {
                                throw new InvalidAPIResponseException("current person endpoint does not contain a person object");
                            }
                        }
                    } else {
                        curl_close($req2);
                        throw new InvalidAuthResponseException("The GIS auth response does not match the requirements.");
                    }

                } else {
                    curl_close($req);
                    curl_close($req2);
                    throw new InvalidAPIResponseException($res->status->message);
                }
            } elseif(isset($current_person->person)) {
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

                throw new InvalidAPIResponseException("Response for current person does not contain a person");
            }
        } else  {
            curl_close($req);

            if(strpos($res, "<h2>Invalid email or password.</h2>") !== false) {
                throw new InvalidCredentialsException("Invalid email or password");
            } else {
                throw new InvalidAuthResponseException("The GIS auth response does not match the requirements.");
            }
        }
    }
}