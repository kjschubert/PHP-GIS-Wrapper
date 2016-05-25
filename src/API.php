<?php
/**
 * Created by PhpStorm.
 * User: kjs
 * Date: 23.05.16
 * Time: 18:01
 */

namespace GISwrapper;


class API
{
    protected $_cache;
    protected $_subs;
    protected $_auth;
    protected $_pathParams;

    public function __construct($cache, $auth, $pathParams = array())
    {
        $this->_cache = $cache;
        $this->_subs = array();
        $this->_auth = $auth;
        $this->_pathParams = $pathParams;
    }

    public function __get($name)
    {
        if(array_key_exists($name, $this->_cache['subs']) && !$this->_cache['subs'][$name]['dynamic']) {
            if(!isset($this->_subs[$name])) {
                $this->_subs[$name] = APISubFactory::factory($this->_cache['subs'][$name], $this->_auth);
            }
        } else {
            trigger_error("Property " . $name . " does not exist.", E_USER_WARNING);
            return null;
        }
    }

    public function __isset($name)
    {
        return array_key_exists($name, $this->_cache);
    }
}