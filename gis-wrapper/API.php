<?php
namespace GIS;

require_once( dirname(__FILE__) . '/Endpoint.php' );

/**
 * Class APIFactory
 *
 * class to factory an API object
 *
 * @author Karl Johann Schubert <karljohann.schubert@aiesec.de>
 * @version 0.1
 * @package GIS
 */
class APIFactory {

    /**
     * @param $name the name of the API
     * @param $manifest the manifest (swagger file) of the API
     * @param $auth the current auth provider
     * @returns either an API object if the API does not have a root endpoint or else a RootEndpointAPI object
     */
    public static function factory($name, $manifest, $auth) {
        foreach($manifest->apis as $api) {
            if( explode('.', basename($api->path))[0] == $name) return new RootEndpointAPI($name, $manifest, $auth);
        }
        return new API($name, $manifest, $auth);
    }
}

/**
 * Class API
 *
 * provides an API without an root endpoint
 *
 * @author Karl Johann Schubert <karljohann.schubert@aiesec.de>
 * @version 0.1
 * @package GIS
 */
class API {

    /**
     * @var \GIS\AuthProvider
     */
    protected $_auth;

    /**
     * @var String
     */
    protected $_name;

    /**
     * @var Array
     */
    protected $_endpoints = array();

    /**
     * @var Array
     */
    protected $_apis = array();

    /**
     * @var mixed
     */
    protected $_dynamicEndpoint = null;

    /**
     * @var mixed
     */
    protected $_dynamicAPI = null;

    /**
     * @var Mixed
     */
    protected $_rootEndpoint = null;

    /**
     * @var String
     */
    protected $_basePath;

    /**
     * @var Array
     */
    protected $_dynamicAPIs = array();

    /**
     * @var Array
     */
    protected $_dynamicEndpoints = array();

    /**
     * @var Array
     */
    protected $_dynamicKeep = array();

    /**
     * @var mixed
     */
    protected $_instance = null;

    /**
     * @var Array
     */
    protected $_values = array();

    /**
     * @var bool
     */
    protected $_isRoot;

    /**
     * @param $name the name of the API
     * @param $manifest the manifest (swagger file) of the API
     * @param $auth the current auth provider
     * @throws \GIS\EndpointDuplicationException if the same path is used by more than one endpoint
     * @throws \GIS\InvalidAPIPathException if the endpoint path does not fit the scheme
     */
    public function __construct($name, $manifest, $auth, $isRoot = false) {
        $this->_name = $name;
        $this->_auth = $auth;
        $this->_basePath = $manifest->basePath;
        $this->_isRoot = $isRoot;

        if(!isset($manifest->name)) $manifest->name = $name;

        foreach($manifest->apis as $api) {
            if(dirname($api->path) == '/' . $manifest->apiVersion) {    // check if it is the root endpoint of this API
                /*
                 * if it is, we directly add it as root endpoint.
                 * The class RootEndpointApi is only there because it needs to implement the interface \Iterator
                 * to switch between the functionality of the API class and the Endpoint class
                 */
                $this->_rootEndpoint = $api;
            } else if(dirname($api->path) == '/' . $manifest->apiVersion . '/' . $manifest->name) {    //check if it could be a normal endpoint of this API
                // name of the endpoint
                $api->name = $n = explode('.', basename($api->path))[0];

                if( strpos($n, '{' ) !== false ) {   // check if it is a dynamic endpoint
                    if($this->_dynamicEndpoint !== null) throw new EndpointDuplicationException("There is already a dynamic endpoint registered,");

                    if($this->_dynamicAPI !== null) {   //check if there is already a dynamic API
                        if($this->_dynamicAPI->name != $n) throw new EndpointDuplicationException("Can not add dynamic endpoint " . $n . ". There is already a dynamic API with a different path.");
                        $this->_dynamicAPI->apis[] = $api;
                    } else {
                        $this->_dynamicEndpoint = $api;
                    }
                } else if(array_key_exists($n, $this->_apis)) { // check if it is the root endpoint of a already registered sub api
                    $this->_apis[$n]->apis[] = $api;
                } else {    // then it is an endpoint of this api
                    if(array_key_exists($n, $this->_endpoints)) throw new EndpointDuplicationException("Endpoint " . $n . " is already existing.");
                    $this->_endpoints[$n] = $api;
                }
            } else if( strpos( dirname( $api->path ), '/' . $manifest->apiVersion . '/' . $manifest->name ) === 0 ) {      // check if it is part of an sub api
                // determine api name ($an)
                $an = substr( dirname( $api->path ), strlen('/' . $manifest->apiVersion . '/' . $manifest->name) );
                $an = ltrim($an, "/");
                $an = explode("/", $an)[0];


                if( array_key_exists( $an, $this->_apis ) ) {   // check if static sub api is already created
                    $this->_apis[$an]->apis[] = $api;
                } elseif($this->_dynamicAPI !== null && strpos($an, '{') !== false) { // if it is an dynamic sub api and there is already a dynamic sub api
                    // check that the paths are the same
                    if ($this->_dynamicAPI->name != $an) throw new EndpointDuplicationException($this->_dynamicAPI->name . " and " . $an . " are both dynamic sub apis.");

                    $this->_dynamicAPI->apis[] = $api;
                } else {    // create a new sub api
                    // create sub api
                    $a = new \stdClass();
                    $a->basePath = $this->_basePath;
                    $a->apiVersion = $manifest->apiVersion . '/' . $manifest->name;
                    $a->apis = array($api);
                    $a->name = $an;

                    if(strpos($an, '{') !== false) {    // dynamic sub api
                        if($this->_dynamicEndpoint !== null) {
                            if($this->_dynamicEndpoint->name != $an) throw new EndpointDuplicationException("Can not add dynamic API " . $an . ", because there is already a dynamic endpoint with a different path");
                            $a->apis[] = $this->_dynamicEndpoint;
                            $this->_dynamicEndpoint = null;
                        }
                        $this->_dynamicAPI = $a;
                    } else {    // static sub api
                        // search in the endpoints of the current API if the root endpoint of the sub api is already added and if yes, add id to the sub api and delete it from the endpoints here.
                        if(array_key_exists( $an, $this->_endpoints )) {
                            $a->apis[] = $this->_endpoints[ $an ];
                            unset($this->_endpoints[ $an ]);
                        }
                        $this->_apis[$an] = $a;
                    }
                }
            } else {
                throw new InvalidAPIPathException($api->path . " is not a valid API path");
            }
        }
    }

    /**
     * @param $name the attribute name
     * @returns mixed the attributes value
     */
    public function __get($name) {
        if(trim($name) != "" && trim($name) != "_") {
            $old = $this->_dynamicKeep;
            $this->_dynamicKeep[] = $name;
            $this->garbageCollection();
            $this->_dynamicKeep = $old;

            if (array_key_exists($name, $this->_apis)) {
                return $this->getAPI($name);
            } else if (array_key_exists($name, $this->_endpoints)) {
                return $this->getEndpoint($name);
            } else if ($this->_dynamicAPI !== null) {
                if (substr($name, 0, 1) == "_") $name = substr($name, 1);
                return $this->getAPI($name);
            } else if ($this->_dynamicEndpoint !== null) {
                if (substr($name, 0, 1) == "_") $name = substr($name, 1);
                return $this->getEndpoint($name);
            }
        }
        return null;
    }

    /**
     * @param $name
     * @return mixed
     * @throws InvalidAPIException
     */
    private function getAPI($name) {
        if( array_key_exists($name, $this->_apis)) {
            if(!($this->_apis[$name] instanceof API)) {
                $this->_apis[$name] = APIFactory::factory($name, $this->_apis[$name], $this->_auth);
            }
        } else if( $this->_dynamicAPI !== null ) {
            if(!($this->_dynamicAPI instanceof API)) $this->_dynamicAPI = APIFactory::factory($this->_dynamicAPI->name, $this->_dynamicAPI, $this->_auth);
            $this->_apis[$name] = $this->_dynamicAPI->getInstance($name);
            array_push($this->_dynamicAPIs, $name);
        } else {
            throw new InvalidAPIException("The api " . $name . " does not exist.");
        }
        $this->_apis[$name]->setValues($this->_values);
        return $this->_apis[$name];
    }

    /**
     * @param $name
     * @return mixed
     * @throws InvalidEndpointException
     */
    private function getEndpoint($name) {
        if( array_key_exists($name, $this->_endpoints)) {
            if(!($this->_endpoints[$name] instanceof Endpoint)) {
                $this->_endpoints[$name] = new Endpoint($name, $this->_endpoints[$name], $this->_auth);
            }
        } else if( $this->_dynamicEndpoint !== null ) {
            if(!($this->_dynamicEndpoint instanceof Endpoint)) {
                $this->_dynamicEndpoint = new Endpoint($this->_dynamicEndpoint->name, $this->_dynamicEndpoint, $this->_auth);
            }
            $this->_endpoints[$name] = $this->_dynamicEndpoint->getInstance($name);
            array_push($this->_dynamicEndpoints, $name);
        } else {
            throw new InvalidEndpointException("The Endpoint " . $name . " does not exist.");
        }
        $this->_endpoints[$name]->setValues($this->_values);
        return $this->_endpoints[$name];
    }

    /**
     * Cleans up the instances of dynamic endpoints and apis
     *
     * @param int $max
     * @param array|String $keep
     */
    public function garbageCollection($max = 5, $keep = array()) {
        if(!is_array($keep)) $keep = array($keep);
        $a = $b = $max;
        while(count($this->_dynamicAPIs) > $a) {
            $name = array_shift($this->_dynamicAPIs);
            if(in_array($name, $this->_dynamicKeep) || in_array($name, $keep)) {
                $a++;
                array_push($this->_dynamicAPIs, $name);
            } else {
                unset($this->_apis[$name]);
            }
        }
        while(count($this->_dynamicEndpoints) > $b) {
            $name = array_shift($this->_dynamicEndpoints);
            if(in_array($name, $this->_dynamicKeep) || in_array($name, $keep)) {
                $b++;
                array_push($this->_dynamicEndpoints, $name);
            } else {
                unset($this->_endpoints[$name]);
            }
        }
    }

    /**
     * @param mixed $name name of the instance. Equals the value of the identifying attribute.
     * @return API
     * @throws InvalidChangeException when the temporary clone of the master instance accidently gets an instance name during cloning
     * @throws InvalidMasterException when you try to retrieve a new instance from an old instance an not from the master instance
     */
    public function getInstance($name) {
        if($this->_instance !== null) throw new InvalidMasterException("Can not get a instance from a instance only from the master object");
        $tmp = clone $this;
        $tmp->setInstance($name);
        return $tmp;
    }

    /**
     * @param $name name of the instance. Equals the value of the identifying attribute.
     * @throws InvalidChangeException when this instance is not a master instance
     */
    public function setInstance($name) {
        if($this->_instance === null) {
            $this->_instance = $name;
            $this->setValues($this->_values);
        } else {
            throw new InvalidChangeException("You can not change the name of a instance.");
        }
    }

    /**
     * This function is only to pass the instance names through the sub apis. It should not be called from outside of an API object
     * @param $values
     */
    public function setValues($values) {
        $this->_values = $values;
        if($this->_instance !== null) $this->_values[$this->_name] = $this->_instance;
    }

    /**
     * __clone()
     *
     * gets called when an API object is cloned. This is normally the case, when we retrieve a new instance of a dynamic API. Therefore all the instances of dynamic apis and endpoints are deleted as well as the static apis and endpoints are cloned.
     */
    function __clone() {
        $tmp = $this->_apis;
        $this->_apis = array();
        foreach($tmp as $name => $api) {
            if($name == ($api instanceof API) ? $api->getAPIName() : $api->name) {
                $this->_apis[$name] = clone $api;
            }
        }
        $this->_dynamicAPIs = array();

        $tmp = $this->_endpoints;
        $this->_endpoints = array();
        foreach($tmp as $name => $ep) {
            if($name == ($ep instanceof Endpoint) ? $ep->getAPIName() : $ep->name) {
                $this->_endpoints[$name] = clone $ep;
            }
        }
        $this->_dynamicEndpoints = array();
        $this->_dynamicKeep = array();
    }

    /**
     * @return String the name of the API
     */
    public function getName() {
        return $this->_name;
    }

    /**
     * resets all the sub apis and endpoints
     */
    public function reset() {
        $this->garbageCollection(0);
        foreach($this->_endpoints as $e) {
            if($e instanceof Endpoint) $e->reset();
        }
        foreach($this->_apis as $a) {
            if($a instanceof API) $a->reset();
        }
    }
}

/**
 * Class RootEndpointAPI
 *
 * extends the API class by implementing the Iterator interface for those APIs with an root endpoint
 *
 * @author Karl Johann Schubert <karljohann.schubert@aiesec.de>
 * @version 0.1
 * @package GIS
 */
class RootEndpointAPI extends API implements \Iterator {

    public function __construct($name, $manifest, $auth) {
        parent::__construct($name, $manifest, $auth);
    }

    public function __get($name) {
        $this->checkEndpoint();
        $tmp = $this->_rootEndpoint->{$name};
        if($tmp === null) {
            $tmp = parent::__get($name);
        }
        return $tmp;
    }

    public function __set($name, $value) {
        $this->checkEndpoint();
        $this->_rootEndpoint->$name = $value;
    }

    public function __clone() {
        parent::__clone();
        $this->_rootEndpoint = clone $this->_rootEndpoint;
    }

    /**
     * checks that the root endpoint is instantiated an updates the values
     */
    private function checkEndpoint() {
        if(!($this->_rootEndpoint instanceof Endpoint)) {
            $this->_rootEndpoint = new Endpoint($this->_name, $this->_rootEndpoint, $this->_auth);
        }
        $this->_rootEndpoint->setValues($this->_values);
    }

    /*
     * Endpoint pass-through functions
     */

    /**
     * Endpoint pass-through function
     * @return mixed
     */
    public function current() {
        $this->checkEndpoint();
        return $this->_rootEndpoint->current();
    }

    /**
     * Endpoint pass-through function
     * @return mixed
     */
    public function next() {
        $this->checkEndpoint();
        return $this->_rootEndpoint->next();
    }

    /**
     * Endpoint pass-through function
     * @return mixed
     */
    public function key() {
        $this->checkEndpoint();
        return $this->_rootEndpoint->key();
    }

    /**
     * Endpoint pass-through function
     * @return mixed
     */
    public function valid() {
        $this->checkEndpoint();
        return $this->_rootEndpoint->valid();
    }

    /**
     * Endpoint pass-through function
     * @return mixed
     */
    public function rewind() {
        $this->checkEndpoint();
        return $this->_rootEndpoint->rewind();
    }

    /**
     * Endpoint pass-through function
     * @return mixed
     */
    public function count() {
        $this->checkEndpoint();
        return $this->_rootEndpoint->count();
    }

    /**
     * Endpoint pass-through function
     * @return mixed
     */
    public function facets() {
        $this->checkEndpoint();
        return $this->_rootEndpoint->facets();
    }

    /**
     * Endpoint pass-through function
     * @return mixed
     */
    public function reset() {
        parent::reset();
        if($this->_rootEndpoint instanceof Endpoint) $this->_rootEndpoint->reset();
    }

    /**
     * Endpoint pass-through function
     * @return mixed
     */
    public function get() {
        $this->checkEndpoint();
        return $this->_rootEndpoint->get();
    }

    /**
     * Endpoint pass-through function
     * @return mixed
     */
    public function getParams(){
        $this->checkEndpoint();
        return $this->_rootEndpoint->getParams();
    }
}