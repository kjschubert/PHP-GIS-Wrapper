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
    private $_cache;
    private $_subs;

    public function __construct($cache)
    {
        $this->_cache = $cache;
    }

    public function __get($name)
    {
        if(array_key_exists($name, $this->_subs)) {
            return $this->_subs[$name];
        } elseif(array_key_exists($name, $this->_cache)) {
            if(!is_array($this->_cache[$name])) {
                $this->_cache[$name] = $this->proceedSubCache($this->_cache[$name]);
            }
            if($this->_cache[$name]['endpoint']) {
                $this->_subs[$name] = new Endpoint($this->_cache[$name]);
            } else {
                $this->_subs[$name] = new API($this->_cache[$name]);
            }
            return $this->_subs[$name];
        } else {
            return null;
        }
    }

    public function __isset($name)
    {
        return array_key_exists($name, $this->_cache);
    }
}