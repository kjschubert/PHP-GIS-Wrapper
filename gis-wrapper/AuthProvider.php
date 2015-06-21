<?php

namespace GIS;

require_once( dirname(__FILE__) . '/Exceptions.php' );

/**
 * Interface AuthProvider
 *
 * master class for authentication Provider
 *
 * @author Karl Johann Schubert <karljohann.schubert@aiesec.de>
 * @version 0.1
 * @package GIS
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