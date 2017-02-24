<?php
namespace GISwrapper;

/**
 * Class AuthProviderEXPA
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @version 0.2
 * @package GISwrapper
 */
class AuthProviderEXPA extends AuthProviderCombined {

    /**
     * generateNewToken()
     *
     * function that performs a login with GIS auth to get a new EXPA access token, without validating the token
     *
     * @throws InvalidCredentialsException if the username or password is invalid
     */
    protected function generateNewToken() {
        // set type to EXPA
        $this->_type = true;

        // run the GIS Auth Flow for EXPA
        $this->GISauthFlow('https://auth.aiesec.org/oauth/authorize?redirect_uri=https%3A%2F%2Fexperience.aiesec.org%2Fsign_in&response_type=code&client_id=349321fd15814e9fdd2c5abe062a6fb10a27a95dd226fce287adb6c51d3de3df');
    }
}