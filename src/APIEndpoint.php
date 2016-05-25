<?php
namespace GISwrapper;

class APIEndpoint extends API
{
    private $_params;

    /**
     * @var int
     * value for the current page of paged endpoints, declared here for use in the get method
     */
    protected $_currentPage;
    
    function __construct($cache, $auth, $pathParams = array())
    {
        parent::__construct($cache, $auth, $pathParams);
        $this->_params = array();
    }

    public function __get($name)
    {
        if(isset($this->_cache['subs'][$name]) && !$this->_params['subs'][$name]['dynamic']) {
            if(!isset($this->_subs[$name])) {
                $this->_subs[$name] = APISubFactory::factory($this->_cache['subs'][$name], $this->_auth);
            }
            return $this->_subs[$name];
        } elseif(isset($this->_cache['params'][$name])) {
            if(!isset($this->_params[$name])) {
                $this->_params[$name] = ParameterFactory::factory($this->_cache['params'][$name]);
            }
            return $this->_params[$name];
        } else {
            trigger_error("Property " . $name . " does not exist", E_USER_WARNING);
            return null;
        }
    }
    
    public function __set($name, $value)
    {
        if(isset($this->_cache['subs'][$name]) && !$this->_params['subs'][$name]['dynamic']) {
            trigger_error('Property ' . $name . ' is not a parameter', E_USER_ERROR);
        } elseif(isset($this->_cache['params'][$name])) {
            if(!isset($this->_params[$name])) {
                $this->_params[$name] = ParameterFactory::factory($this->_cache['params'][$name]);
            }
            if(is_scalar($value)) {
                $this->_params[$name]->set($value);
            } elseif(is_array($value)) {
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
            trigger_error("Property " . $name . " does not exist", E_USER_WARNING);
            return null;
        }
    }

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

    public function __isset($name)
    {
        return isset($this->_subs[name]) || isset($this->_params[$name]);
    }

    public function exists($name) {
        return array_key_exists($name, $this->_cache['subs']) || array_key_exists($name, $this->_cache['params']);
    }

    public function getOperations() {
        return $this->_cache['operations'];
    }

    public function valid($operation) {
        if(in_array($operation, $this->_cache['operations'])) {
            foreach($this->_cache['params'] as $name => $param) {
                if(isset($this->_params[$name])) {
                    if (!$this->_params[$name]->valid($operation)) return false;
                } elseif($param['operations'][$operation]['required']) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    public function get() {
        if(in_array('GET', $this->_cache['operations'])) {
            if($this->valid('GET')) {
                $url = $this->baseUrl();
                $params = $this->gatherParams('GET');

                // build url
                if(count($params) > 0) {
                    $params = http_build_query($params);

                    // gis hack (replace numerical array keys with no array key
                    $params = preg_replace('/(%5B[0-9]*%5D)/', '%5B%5D', $params);

                    $url .= '?' . $params . '&';
                } else {
                    $url .= '?';
                }

                // check if we need to add a page
                if(isset($this->_currentPage)) {
                    $url .= 'page=' . $this->_currentPage . '&';
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
    
    public function update() {
        return $this->payloadRequest('PATCH');
    }
    
    public function create() {
        return $this->payloadRequest('POST');
    }
    
    public function delete() {
        if(in_array('DELETE', $this->_cache['operations'])) {
            if ($this->valid('DELETE')) {
                $url = $this->baseUrl();
                $params = $this->gatherParams('DELETE');

                // build url
                if (count($params) > 0) {
                    $params = http_build_query($params);

                    // gis hack (replace numerical array keys with appending array key
                    $params = preg_replace('/(%5B[0-9]*%5D)/', '%5B%5D', $params);

                    $url .= '?' . $params . '&';
                } else {
                    $url .= '?';
                }

                $req = curl_init();
                curl_setopt($req, CURLOPT_CUSTOMREQUEST, "DELETE");
                curl_setopt($req, CURLOPT_RETURNTRANSFER, true);

                $attempts = 0;
                $res = false;
                while(!$res && $attempts < 3) {
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

    public function reset() {
        $this->_params = array();
    }

    private function payloadRequest($operation) {
        if(in_array($operation, $this->_cache['operations'])) {
            if ($this->valid($operation)) {
                $url = $this->baseUrl();
                $params = json_encode($this->gatherParams($operation));

                $req = curl_init();
                curl_setopt($req, CURLOPT_RETURNTRANSFER, true);

                $res = false;
                $attempts = 0;
                while(!$res && $attempts < 3) {
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

    private function proceedStatus($status) {
        if($status->code == "403" && $status->message == "Active role required to view this content.") {
            throw new ActiveRoleRequiredException("Active role required to view this content. This mostly happens when an non-active person login through EXPA. URL: " . $url . "access_token=" . $token . "");
        } elseif($status->code == "403" && $status->message == "You are not authorized to perform the requested action") {
            throw new UnauthorizedException("You are not authorized to perform the requested action");
        } else {
            throw new InvalidAPIResponseException($status->message);
        }
    }

    private function baseUrl() {
        // start with endpoint path
        $url = $this->_cache['path'];

        // replace all dynamic path parts
        foreach($this->_pathParams as $name => $value) {
            $url = str_replace($name, $value, $url);
        }

        return $url;
    }

    private function gatherParams($operation) {
        $data = array();
        foreach($this->_params as $name => $param) {
            $v = $param->getRequestValue($operation);
            if($v != null) $data[$name] = $v;
        }

        return $data;
    }
}