<?php
namespace GISwrapper;

/**
 * Class AuthProviderShadow
 * 
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @package GISwrapper
 * @version 0.2
 */
class AuthProviderShadow implements AuthProvider
{
    /**
     * @var string
     */
    private $_token;

    /**
     * @var AuthProvider
     */
    private $_authProvider;

    /**
     * AuthProviderShadow constructor.
     * 
     * @param $token string current authentication token
     * @param null|AuthProvider $authProvider optional AuthProvider to generate new tokens
     */
    public function __construct($token, $authProvider = null)
    {
        $this->_token = $token;
        $this->_authProvider = $authProvider;
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
        return $this->_token;
    }

    /**
     * getNewToken()
     *
     * function that generates a new access token and returns it
     *
     * @return String new access token
     * @throws InvalidCredentialsException
     */
    public function getNewToken()
    {
        if($this->_authProvider instanceof AuthProvider) {
            $this->_token = $this->_authProvider->getNewToken();
            return $this->_token;
        } else {
            throw new InvalidCredentialsException("Could not get token from sub auth provider");
        }
    }

    /**
     * @return AuthProvider|null
     */
    public function getAuthProvider() {
        return $this->_authProvider;
    }
}