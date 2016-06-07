<?php
namespace GISwrapper;

/**
 * Class AuthProviderNationalIdentity
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @package GISwrapper
 * @version 0.2
 */
class AuthProviderNationalIdentity implements AuthProvider
{

    /**
     * URL for custom flow
     * @var string
     */
    private $_url;

    /**
     * @var string
     */
    private $_token;

    /**
     * @var integer timestamp
     */
    private $_expires_at;

    /**
     * AuthProviderNationalIdentity constructor.
     * @param $url string full url for the custom token flow
     */
    public function __construct($url)
    {
        $this->_url = $url;
    }

    /**
     * getToken()
     *
     * function that returns the current access token
     *
     * @return String access token
     */
    public function getToken()
    {
        if($this->_expires_at < time() || is_null($this->_expires_at)) {
            $this->generateNewToken();
        }
        return $this->_token;
    }

    /**
     * getNewToken()
     *
     * function that generates a new access token and returns it
     *
     * @return String new access token
     */
    public function getNewToken()
    {
        $this->generateNewToken();
        return $this->_token;
    }

    /**
     * @return integer timestamp
     */
    public function getExpiresAt() {
        return $this->_expires_at;
    }

    /**
     * get a new token from the national identity service
     *
     * @throws NoResponseException
     * @throws InvalidAuthResponseException
     * @throws InvalidCredentialsException
     */
    private function generateNewToken() {
        $res = false;
        $attempts = 0;
        while($res === FALSE && $attempts < 3)
        {
            $res = @file_get_contents($this->_url);
            if($res !== FALSE)
            {
                $res = json_decode($res);
                if($res === null) {
                    $res = false;
                }
            }
            $attempts++;
        }
        if($res !== false) {
            if(isset($res->data)) {
                $this->_token = $res->data->access_token;
                $this->_expires_at = strtotime($res->data->expires_at);
            } elseif(isset($res->status)) {
                throw new InvalidCredentialsException($res->status->message);
            } else {
                throw new InvalidAuthResponseException("Invalid response from national identity service");
            }
        } else {
            throw new NoResponseException("No response from national identity service");
        }
    }
}