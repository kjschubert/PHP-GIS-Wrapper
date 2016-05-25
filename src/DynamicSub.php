<?php
/**
 * Created by PhpStorm.
 * User: kjs
 * Date: 24.05.16
 * Time: 17:26
 */

namespace GISwrapper;


class DynamicSub implements \ArrayAccess
{
    private $_auth;
    private $_cache;
    private $_pathParams;
    private $_dynamicSub;
    private $_dynamicInstances;

    public function __construct($cache, $auth, $pathParams = array())
    {
        $this->_auth = $auth;
        $this->_cache = $cache;
        $this->_pathParams = $pathParams;
        $this->_dynamicInstances = array();

        // find dynamic sub
        $this->_dynamicSub = null;
        foreach($cache['subs'] as $name => $sub) {
            if($sub['dynamic']) {
                if($this->_dynamicSub != null) {
                    throw new RequirementInvalidEndpointException("Found a second dynamic endpoint as sub endpoint");
                } else {
                    $this->_dynamicSub = $name;
                }
            }
        }
        if($this->_dynamicSub == null) {
            throw new RequirementInvalidEndpointException("Could not find dynamic endpoint");
        }
    }

    public function offsetExists($offset)
    {
        $url = $this->_cache['subs'][$this->_dynamicSub]['path'];

        // replace all dynamic path parts
        foreach($this->_pathParams as $name => $value) {
            $url = str_replace($name, $value, $url);
        }

        // insert offset in the request
        $url = str_replace($this->_dynamicSub, $offset, $url) . '?';

        // get request
        $res = GET::request($url, $this->_auth);

        // check if object exists
        if(is_object($res)) {
            if(isset($res->status) && ($res->status->code == 404 || $res->status->code == 500)) {
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    public function offsetGet($offset)
    {
        if(!isset($this->_dynamicInstances[$offset])) {
            $pathParams = $this->_pathParams;
            $pathParams[$this->_dynamicSub] = $offset;
            $this->_dynamicInstances[$offset] = APISubFactory::factory($this->_cache['subs'][$this->_dynamicSub], $this->_auth, $pathParams);
        }
        return $this->_dynamicInstances[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if(is_object($value) && ($value instanceof API || is_subclass_of($value, API::class))) {
            $this->_dynamicInstances[$offset] = $value;
        } else {
            trigger_error("Object for offset " . $offset . " does not have the right type", E_USER_ERROR);
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->_dynamicInstances[$offset]);
    }

    public function reset() {
        $this->_dynamicInstances = array();
    }
}