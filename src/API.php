<?php
namespace GISwrapper;

/**
 * Class API
 * represents a part of the path which is not an data endpoint
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @package GISwrapper
 * @version 0.2
 */
class API
{
    /**
     * @var array
     */
    protected $_cache;

    /**
     * @var array
     */
    protected $_subs;

    /**
     * @var AuthProvider
     */
    protected $_auth;

    /**
     * @var array
     */
    protected $_pathParams;

    /**
     * API constructor.
     * @param array $cache parsed swagger file for this api
     * @param AuthProvider $auth
     * @param array $pathParams array with values for dynamic parts of the path
     */
    public function __construct($cache, $auth, $pathParams = array())
    {
        $this->_cache = $cache;
        $this->_subs = array();
        $this->_auth = $auth;
        $this->_pathParams = $pathParams;
    }

    /**
     * @param mixed $name property name
     * @return mixed|null value for the property
     */
    public function __get($name)
    {
        if(array_key_exists($name, $this->_cache['subs']) && !$this->_cache['subs'][$name]['dynamic']) {
            if(!isset($this->_subs[$name])) {
                $this->_subs[$name] = APISubFactory::factory($this->_cache['subs'][$name], $this->_auth);
            }
            return $this->_subs[$name];
        } else {
            trigger_error("Property " . $name . " does not exist.", E_USER_WARNING);
            return null;
        }
    }

    /**
     * @param mixed $name property name
     * @return bool indicating if there is a active instance of this property
     */
    public function __isset($name)
    {
        return isset($this->_subs[$name]);
    }

    /**
     * @param mixed $name property name
     * deletes the current instance of the property
     */
    public function __unset($name) {
        if(isset($this->_subs[$name])) {
            unset($this->_subs[$name]);
        }
    }

    /**
     * @param mixed $name property name
     * @return bool indicating if the property exists (this doesn't mean that there is a instance of the property). The existence of the instance is indicated by isset
     */
    public function exists($name) {
        return (isset($this->_cache['subs'][$name]) && !$this->_cache['subs'][$name]['dynamic']);
    }
}