<?php
namespace GISwrapper;

/**
 * Class APIEndpoint
 * representing a part of the path which returns data
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de
 * @package GISwrapper
 * @version 0.2
 */
class APIEndpoint extends API
{
    /**
     * @var array
     */
    private $_params;

    /**
     * @var int
     * value for the current page of paged endpoints, declared here for use in the get method
     */
    protected $_currentPage;

    /**
     * @var int
     * value for parameter per_page of paged endpoint, declared here for use in the get method
     */
    protected $_perPage;

    /**
     * APIEndpoint constructor.
     * @param array $cache parsed swagger file for this api
     * @param AuthProvider $auth
     * @param array $pathParams array with values for dynamic parts of the path
     */
    function __construct($cache, $auth, $pathParams = array())
    {
        parent::__construct($cache, $auth, $pathParams);
        $this->_params = array();
    }

    /**
     * @param mixed $name property name
     * @return mixed|null property value
     */
    public function __get($name)
    {
        if(isset($this->_cache['subs'][$name]) && !$this->_cache['subs'][$name]['dynamic']) {
            if(!isset($this->_subs[$name])) {
                $this->_subs[$name] = APISubFactory::factory($this->_cache['subs'][$name], $this->_auth, $this->_pathParams);
            }
            return $this->_subs[$name];
        } elseif(isset($this->_cache['params'][$name])) {
            if(!isset($this->_params[$name])) {
                $this->_params[$name] = ParameterFactory::factory($this->_cache['params'][$name]);
            }
            if($this->_params[$name]->hasChilds() || $this->_params[$name] instanceof ParameterArrayType) {
                return $this->_params[$name];
            } else {
                return $this->_params[$name]->get();
            }
        } else {
            trigger_error("Property " . $name . " does not exist", E_USER_ERROR);
            return null;
        }
    }

    /**
     * @param mixed $name name of the property to change
     * @param mixed $value new value for the property
     * @return void
     */
    public function __set($name, $value)
    {
        if(isset($this->_cache['subs'][$name]) && !$this->_cache['subs'][$name]['dynamic']) {
            trigger_error('Property ' . $name . ' is not a parameter', E_USER_ERROR);
        } elseif(isset($this->_cache['params'][$name])) {
            if(is_scalar($value) || $value instanceof \DateTime) {
                if(!isset($this->_params[$name])) {
                    $this->_params[$name] = ParameterFactory::factory($this->_cache['params'][$name]);
                }
                $this->_params[$name]->set($value);
            } elseif(is_array($value)) {
                // recreate param to reset other keys and keep scalar value if existent
                $v = ((isset($this->_params[$name]) && is_scalar($this->_params[$name]->value())) ? $this->_params[$name]->value() : null);
                $this->_params[$name] = ParameterFactory::factory($this->_cache['params'][$name]);
                $this->_params[$name]->value($v);

                // proceed array
                if($this->_params[$name] instanceof ParameterArrayType) {
                    foreach($value as $key => $v) {
                        $this->_params[$name][$key] = $v;
                    }
                } else {
                    foreach($value as $key => $v) {
                        $this->_params[$name]->$key = $v;
                    }
                }
            } else {
                trigger_error("Invalid value for property " . $name, E_USER_ERROR);
            }
        } else {
            trigger_error("Property " . $name . " does not exist", E_USER_ERROR);
        }
    }

    /**
     * @param mixed $name property name of the instance to delete
     */
    public function __unset($name)
    {
        if(isset($this->_subs[$name])) {
            unset($this->_subs[$name]);
        } elseif(isset($this->_params[$name])) {
            unset($this->_params[$name]);
        } elseif(!array_key_exists($name, $this->_cache['subs']) && !array_key_exists($name, $this->_cache['params'])) {
            trigger_error("Property " . $name . " does not exist", E_USER_WARNING);
        }
    }

    /**
     * @param mixed $name property name
     * @return bool indicating if this property is instantiated
     */
    public function __isset($name)
    {
        return isset($this->_subs[$name]) || isset($this->_params[$name]);
    }

    /**
     * @param mixed $name property name
     * @return bool indicating if this property exists (Does not mean it is instantiated. Use isset for this)
     */
    public function exists($name) {
        return ((isset($this->_cache['subs'][$name]) && !$this->_cache['subs'][$name]['dynamic']) || isset($this->_cache['params'][$name]));
    }

    /**
     * @return array containing the http Methods of this endpoint
     */
    public function getOperations() {
        return $this->_cache['operations'];
    }

    /**
     * @param string $operation http method to check
     * @return bool indicating if all parameters are valid for the http method to check
     */
    public function valid($operation) {
        if(in_array($operation, $this->_cache['operations'])) {
            foreach($this->_cache['params'] as $name => $param) {
                if(isset($this->_params[$name])) {
                    if (!$this->_params[$name]->valid($operation)) return false;
                } elseif(isset($param['operations'][$operation]) && $param['operations'][$operation]['required']) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * @return object representing the resource returned by this endpoint
     * @throws ActiveRoleRequiredException
     * @throws InvalidAPIResponseException
     * @throws NoResponseException
     * @throws OperationNotAvailableException
     * @throws ParameterRequiredException
     * @throws UnauthorizedException
     */
    public function get() {
        if(in_array('GET', $this->_cache['operations'])) {
            if($this->valid('GET')) {
                $url = $this->getQueryUrl('GET');

                // check if we need to add the page parameter
                if(isset($this->_currentPage)) {
                    $url .= 'page=' . $this->_currentPage . '&';
                }

                // check if we need to add the per_page parameter
                if(isset($this->_perPage)) {
                    $url .= 'per_page=' . $this->_perPage . '&';
                }

                // try to load
                $res = GET::request($url, $this->_auth);

                // validate response
                if(isset($res->status) && isset($res->status->code) && isset($res->status->message)) {
                    $this->proceedStatus($res->status);
                } else {
                    return $res;
                }
            } else {
                throw new ParameterRequiredException('There are one or more required parameters missing for a GET request');
            }
        } else {
            throw new OperationNotAvailableException("Operation GET is not available.");
        }
    }

    /**
     * @return object representing the updated version of the resource
     * @throws OperationNotAvailableException
     * @throws ParameterRequiredException
     */
    public function update() {
        return $this->payloadRequest('PATCH');
    }

    /**
     * @return object representing the created resource
     * @throws OperationNotAvailableException
     * @throws ParameterRequiredException
     */
    public function create() {
        return $this->payloadRequest('POST');
    }

    /**
     * delete the resource at this endpoint
     * @return bool|mixed indicating the success of the request or content of the request response
     * @throws NoResponseException
     * @throws OperationNotAvailableException
     * @throws ParameterRequiredException
     */
    public function delete() {
        if(in_array('DELETE', $this->_cache['operations'])) {
            if ($this->valid('DELETE')) {
                $url = $this->getQueryUrl('DELETE') . 'access_token=';

                $req = curl_init();
                curl_setopt($req, CURLOPT_CUSTOMREQUEST, "DELETE");
                curl_setopt($req, CURLOPT_RETURNTRANSFER, true);

                $attempts = 0;
                $res = false;
                while($res === false && $attempts < 3) {
                    curl_setopt($req, CURLOPT_URL, $url . $this->_auth->getToken());
                    $res = curl_exec($req);
                    if(curl_getinfo($req, CURLINFO_HTTP_CODE) == 401 && $attempts < 2) {
                        $this->_auth->getNewToken();
                        $res = false;
                    }
                    $attempts++;
                }

                return $this->proceedResponse($req, $res);
            } else {
                throw new ParameterRequiredException('There are one or more required parameters missing for a DELETE request');
            }
        } else {
            throw new OperationNotAvailableException("Operation DELETE is not available.");
        }
    }

    /**
     * prepare and send a request with body
     * @param string $operation
     * @return object representing the resource
     * @throws NoResponseException
     * @throws OperationNotAvailableException
     * @throws ParameterRequiredException
     */
    private function payloadRequest($operation) {
        if(in_array($operation, $this->_cache['operations'])) {
            if ($this->valid($operation)) {
                $url = $this->baseUrl() . '?access_token=';
                $params = json_encode($this->gatherParams($operation), JSON_BIGINT_AS_STRING);

                $req = curl_init();
                curl_setopt($req, CURLOPT_RETURNTRANSFER, true);

                $res = false;
                $attempts = 0;
                while($res === false && $attempts < 3) {
                    curl_setopt($req, CURLOPT_URL, $url . $this->_auth->getToken());
                    curl_setopt($req, CURLOPT_CUSTOMREQUEST, $operation);
                    curl_setopt($req, CURLOPT_POSTFIELDS, $params);
                    curl_setopt($req, CURLOPT_HTTPHEADER, array(
                            'Content-Type: application/json',
                            'Content-Length: ' . strlen($params))
                    );

                    $res = curl_exec($req);
                    if(curl_getinfo($req, CURLINFO_HTTP_CODE) == 401 && $attempts < 2) {
                        $this->_auth->getNewToken();
                        $res = false;
                    }
                    $attempts++;
                }

                return $this->proceedResponse($req, $res);
            } else {
                throw new ParameterRequiredException("There are one or more required parameters missing for a $operation request");
            }
        } else {
            throw new OperationNotAvailableException("Operation $operation is not available.");
        }
    }

    /**
     * @param $req the curl request
     * @param $res the response of the curl request
     * @return bool|object indicating success or representing the returned resource
     * @throws ActiveRoleRequiredException
     * @throws InvalidAPIResponseException
     * @throws NoResponseException
     * @throws UnauthorizedException
     */
    private function proceedResponse($req, $res) {
        if($res !== false) {
            if(strlen($res) < 2 && (curl_getinfo($req, CURLINFO_HTTP_CODE) == 200 || curl_getinfo($req, CURLINFO_HTTP_CODE) == 201 || curl_getinfo($req, CURLINFO_HTTP_CODE) == 204)) {
                return true;
            } else {
                $json = json_decode($res);
                if($json === null) {
                    throw new NoResponseException("Invalid JSON");
                } elseif(isset($json->status) && isset($json->status->code) && isset($json->status->message)) {
                    $this->proceedStatus($json->status);
                } else {
                    return $json;
                }
            }
        } else {
            throw new NoResponseException(curl_error($req));
        }
    }

    /**
     * proceeds a returned status object and throws exceptions accordingly
     * @param object $status
     * @throws ActiveRoleRequiredException
     * @throws InvalidAPIResponseException
     * @throws UnauthorizedException
     */
    private function proceedStatus($status) {
        if($status->code == "403" && $status->message == "Active role required to view this content.") {
            throw new ActiveRoleRequiredException("Active role required to view this content. This mostly happens when an non-active person login through EXPA. URL: " . $url . "access_token=" . $token . "");
        } elseif($status->code == "403" && $status->message == "You are not authorized to perform the requested action") {
            throw new UnauthorizedException("You are not authorized to perform the requested action");
        } else {
            throw new InvalidAPIResponseException($status->message);
        }
    }

    /**
     * prepares the url for requests with parameters in the url
     * @param string $operation http method
     * @return string url with all parameters
     */
    private function getQueryUrl($operation) {
        $url = $this->baseUrl();
        $params = $this->gatherParams($operation);

        // build url
        if (count($params) > 0) {
            $params = http_build_query($params);

            // gis hack (replace numerical array keys with appending array key
            $params = preg_replace('/(%5B[0-9]*%5D)/', '%5B%5D', $params);

            $url .= '?' . $params . '&';
        } else {
            $url .= '?';
        }
        return $url;
    }

    /**
     * @return string endpoint path with proceeded dynamic parts
     */
    private function baseUrl() {
        // start with endpoint path
        $url = $this->_cache['path'];

        // replace all dynamic path parts
        foreach($this->_pathParams as $name => $value) {
            $url = str_replace($name, $value, $url);
        }

        return $url;
    }

    /**
     * @param string $operation http method
     * @return array containing the relevant parameter values
     */
    private function gatherParams($operation) {
        $data = array();
        foreach($this->_params as $name => $param) {
            $v = $param->getRequestValue($operation);
            if($v != null) $data[$name] = $v;
        }

        return $data;
    }
}