<?php
namespace GISwrapper;

class Endpoint
{
    private $_params;
    private $_pathParams;
    private $_cache;
    private $_auth;
    
    function __construct($cache, $auth, $pathParams = array())
    {
        $this->_cache = $cache;
        $this->_pathParams = $pathParams;
        $this->_params = array();
        $this->_auth = $auth;
    }

    public function __isset($name)
    {
        return array_key_exists($name, $this->_cache['subs']) || array_key_exists($name, $this->_cache['params']);
    }

    public function __get($name)
    {
        if(isset($this->_cache['subs'][$name])) {
            if(!isset($this->_subs[$name])) {
                $this->_subs[$name] = SubFactory::factory($this->_cache['subs'][$name], $this->_auth);
            }
            return $this->_subs[$name];
        } elseif(isset($this->_cache['params'][$name])) {
            if(!isset($this->_params[$name])) {
                $this->_params[$name] = new Parameter($this->_cache['params'][$name]);
            }
            return $this->_params[$name];
        } else {
            trigger_error("Property " . $name . " does not exist", E_USER_WARNING);
            return null;
        }
    }

    public function getOperations() {
        return $this->_cache['operations'];
    }

    public function valid($operation) {
        if(in_array($operation, $this->_cache['operations'])) {
            foreach($this->_cache['params'] as $name => $param) {
                if($param['operations'][$operation]['required']) {
                    if(isset($this->_params[$name])) {
                        if(!$this->_params[$name]->valid($operation)) return false;
                    } else {
                        return false;
                    }
                }
            }
            return true;
        }
        return false;
    }

    public function get() {
        if(in_array('GET', $this->_cache['operations'])) {
            if($this->valid('GET')) {
                // start with endpoint path
                $url = $this->_cache['path'];

                // replace all dynamic path parts
                foreach($this->_pathParams as $name => $value) {
                    $url = str_replace($name, $value, $url);
                }

                // gather all parameters
                $data = array();
                foreach($this->_params as $name => $param) {
                    $v = $param->getRequestValue('GET');
                    if($v != null) $data[$name] = $v;
                }

                // build url
                if(count($data) > 0) {
                    $url .= '?' . http_build_query($data) . '&';
                } else {
                    $url .= '?';
                }

                // try to load
                $attempts = 0;
                $res = false;
                while(!$res && $attempts < 3) {
                    $res = file_get_contents($url . 'access_token=' . $this->_auth->getToken());
                    if($res !== false) {
                        $res = json_decode($res);
                        if($res !== null) {
                            if(isset($res->status)) {
                                if($res->status->code == '401' && $attempts < 2) {
                                    $res = false;
                                    $this->_auth->getNewToken();
                                }
                            }
                        } else {
                            $res = false;
                        }
                    }
                }

                // validate response
                if($res === false) {
                    throw new NoResponseException("Could not load endpoint " . $url);
                } elseif($res == null) {
                    throw new InvalidAPIResponseException("Invalid Response on " . $url);
                } elseif(isset($res->status)) {
                    if($res->status->code == "403" && $res->status->message == "Active role required to view this content.") {
                        throw new ActiveRoleRequiredException("Active role required to view this content. This mostly happens when an non-active person login through EXPA. URL: " . $url . "access_token=" . $token . "");
                    } else {
                        throw new InvalidAPIResponseException($res->status->message);
                    }
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

    public function reset() {
        $this->_params = array();
    }

    private function load() {

    }

}