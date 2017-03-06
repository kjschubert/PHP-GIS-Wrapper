<?php
namespace GISwrapper;

/**
 * Class AuthProviderOP
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @version 0.2
 * @package GISwrapper
 */
class AuthProviderOP extends AuthProviderCombined {

    /**
     * generateNewToken()
     *
     * function that performs a login with GIS auth to get a new OP access token, without validating the token
     *
     * @throws InvalidCredentialsException if the username or password is invalid
     */
    protected function generateNewToken() {
        // set type to OP
        $this->_type = false;

        // run the GIS Auth Flow for OP
        $this->GISauthFlow('https://auth.aiesec.org/oauth/authorize?redirect_uri=https%3A%2F%2Fopportunities.aiesec.org%2Fauth&response_type=code&client_id=e34d5daf8c89172f7fabccbae8378eb3cb524cffc774c57afe2011b90d2e77e5');
    }
}