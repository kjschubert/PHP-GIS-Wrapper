<?php

namespace GISwrapper;

/**
 * Interface AuthProvider
 *
 * interface for authentication Provider
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @version 0.2
 * @package GISwrapper
 */
interface AuthProvider {

    /**
     * getToken()
     *
     * function that returns the current access token
     *
     * @return String access token
     */
    public function getToken();

    /**
     * getNewToken()
     *
     * function that generates a new access token and returns it
     *
     * @return String new access token
     */
    public function getNewToken();
}