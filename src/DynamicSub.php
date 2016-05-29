<?php
namespace GISwrapper;

/**
 * Class DynamicSub
 * Helper class which contains the logic for dynamic sub parts
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @package GISwrapper
 * @version 0.2
 */
class DynamicSub implements \ArrayAccess
{
    /**
     * @var AuthProvider
     */
    private $_auth;

    /**
     * @var array
     */
    private $_cache;

    /**
     * @var array
     */
    private $_pathParams;

    /**
     * @var string property name of the dynamic sub part
     */
    private $_dynamicSub;

    /**
     * @var array instances of the dynamic sub part
     */
    private $_dynamicInstances;

    /**
     * DynamicSub constructor.
     * @param array $cache parsed swagger file for this api
     * @param AuthProvider $auth
     * @param array $pathParams array with values for dynamic parts of the path
     * @throws RequirementInvalidEndpointException
     */
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

    /**
     * @param mixed $offset
     * @return bool indicating if the resource at this offset exists
     * @throws InvalidAPIResponseException
     * @throws NoResponseException
     */
    public function exists($offset) {
        $url = $this->_cache['subs'][$this->_dynamicSub]['path'];

        // replace all dynamic path parts
        foreach($this->_pathParams as $name => $value) {
            $url = str_replace($name, $value, $url);
        }

        // insert offset in the request
        $url = str_replace($this->_dynamicSub, $offset, $url) . '?';

        // get request
        try {
            $res = GET::request($url, $this->_auth);
        } catch(NoResponseException $e) {
            if(substr($e, 0, 4) == 'http') {
                throw $e;
            } else {
                return false;
            }
        }

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

    /**
     * @param mixed $offset
     * @return bool indicating if this offset is instantiated
     */
    public function offsetExists($offset)
    {
        return isset($this->_dynamicInstances[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed returns the instance at this offset
     */
    public function offsetGet($offset)
    {
        if(!isset($this->_dynamicInstances[$offset])) {
            $pathParams = $this->_pathParams;
            $pathParams[$this->_dynamicSub] = $offset;
            $this->_dynamicInstances[$offset] = APISubFactory::factory($this->_cache['subs'][$this->_dynamicSub], $this->_auth, $pathParams);
        }
        return $this->_dynamicInstances[$offset];
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        if(is_object($value) && ($value instanceof API || is_subclass_of($value, API::class))) {
            $this->_dynamicInstances[$offset] = $value;
        } else {
            trigger_error("Object for offset " . $offset . " does not have the right type", E_USER_ERROR);
        }
    }

    /**
     * @param mixed $offset offset of the instance to be destroyed
     */
    public function offsetUnset($offset)
    {
        unset($this->_dynamicInstances[$offset]);
    }
}