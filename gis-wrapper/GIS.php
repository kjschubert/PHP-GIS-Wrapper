<?php

namespace GIS;

require_once( dirname(__FILE__) . '/Exceptions.php' );
require_once( dirname(__FILE__) . '/API.php' );

/**
 * Class GIS
 *
 * dynamic GIS API wrapper class
 *
 * @author Karl Johann Schubert <karljohann.schubert@aiesec.de>
 * @version 0.1
 * @package GIS
 *
 */
class GIS {
    /**
     * @var \GIS\AuthProvider
     */
    private $_auth;

    /**
     * @var Array
     */
    private $_apis;

    /**
     * @param \GIS\AuthProvider $auth
     * @param URL $apidoc URL of the base swagger file
     * @throws \GIS\InvalidAuthProviderException if $auth doesn't implement the interface \GIS\AuthProvider
     * @throws \GIS\InvalidSwaggerFormatException if the ressource at $apidoc does not fit the swagger format or does not contain any api
     * @throws \GIS\RequirementsException if the API can not produce JSON
     */
    function __construct($auth, $apidoc = "https://gis-api.aiesec.org/v1/docs.json") {
        // check that $auth implements the AuthProvider interface
        if( $auth instanceof AuthProvider ) {
            $this->_auth = $auth;
        } else {
            throw new InvalidAuthProviderException("The given object does not implement the AuthProvider interface.");
        }

        // load APIs
        $res = json_decode($this->GET($apidoc));
        if(!isset($res->apis) || !is_array($res->apis)) throw new InvalidSwaggerFormatException("Could not load apis from swagger base file");
        if(!in_array("application/json", $res->produces)) throw new RequirementsException("API is not able to produce JSON");
        foreach($res->apis as $api) {
            $name = explode('.', basename($api->path))[0];
            $this->_apis[$name] = $res->basePath . str_replace("{format}", "json", $api->path);
        }
    }

    /**
     * provides access to dynamic attributes
     *
     * @param $attr name of the attribute
     * @return mixed the attribute
     * @throws \GIS\InvalidAPIException if $name is not the name of an existing api
     */
    public function __get($name)
    {
        if (array_key_exists($name, $this->_apis)) {
            if(is_string($this->_apis[$name])) {
                $this->_apis[$name] = APIFactory::factory( $name, json_decode( $this->GET( $this->_apis[$name] ) ), $this->_auth, true );
            }
            return $this->_apis[$name];
        } else {
            throw new InvalidAPIException("The API " . $name . " does not exist.");
        }
    }

    /**
     * GET($url)
     *
     * performs a GET request to the given URL and returns the response
     *
     * @param String $url the url for the request
     * @param Array $parameters additional parameters to be added to the url ('name' => 'value')
     * @return String
     */
    private function GET($url/*, $parameters = null*/) {
        /*if($parameters !== null && is_array($parameters)) {
            $url = rtrim($url, '&');
            if(strpos('?', $url) !== false) {
                $url .= '&';
            } else {
                $url .= '?';
            }
            foreach($parameters as $name => $value) {
                $url .= urlencode($name) . '=' . urlencode($value) . '&';
            }
            $url = rtrim($url, '&');
        }*/
        $req = curl_init($url);
        curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($req);
        curl_close($req);
        return $res;
    }

    /**
     * resets all the apis
     */
    public function reset() {
        foreach($this->_apis as $a) {
            if($a instanceof API) $a->reset();
        }
    }

    public function getAPINames() {
        return array_keys($this->_apis);
    }
}