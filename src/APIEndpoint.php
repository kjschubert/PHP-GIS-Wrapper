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

    public function __isset($name)
    {
        return array_key_exists($name, $this->_cache['subs']) || array_key_exists($name, $this->_cache['params']);
    }

    public function __get($name)
    {
        if(isset($this->_cache['subs'][$name]) && !$this->_params['subs'][$name]['dynamic']) {
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
                $url = $this->baseUrl();
                $params = $this->gatherParams('GET');

                // build url
                if(count($params) > 0) {
                    $url .= '?' . http_build_query($params) . '&';
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
    
    public function update() {
        
    }
    
    public function create() {
        
    }
    
    public function delete() {
        
    }

    public function reset() {
        $this->_params = array();
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