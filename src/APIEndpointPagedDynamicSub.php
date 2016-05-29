<?php
namespace GISwrapper;

/**
 * Class APIEndpointPagedDynamicSub
 * representing a part of the path which returns data in pages and has a dynamic subpart
 * @package GISwrapper
 */
class APIEndpointPagedDynamicSub extends APIEndpointPaged implements \ArrayAccess
{
    /**
     * @var DynamicSub
     */
    private $_dynamicSub;

    /**
     * APIEndpointPagedDynamicSub constructor.
     * @param array $cache parsed swagger file for this api
     * @param AuthProvider $auth
     * @param array $pathParams array with values for dynamic parts of the path
     */
    public function __construct($cache, $auth, $pathParams = array())
    {
        parent::__construct($cache, $auth, $pathParams);

        // create instance of DynamicSub class
        $this->_dynamicSub = new DynamicSub($cache, $auth, $pathParams);
    }

    /**
     * @param mixed $name property name
     * @return bool indicating if this property exists (Does not mean there is a instance. Use isset to check if the instance exists)
     */
    public function exists($name) {
        if(!$this->existsSub($name)) {
            return $this->existsDynamicSub($name);
        } else {
            return true;
        }
    }

    /**
     * @param mixed $name sub name
     * @return bool indicating if a subpath with this name exists (Does not mean there is a instance. Use isset to check if the instance exists)
     */
    public function existsSub($name) {
        return parent::exists($name);
    }

    /**
     * @param mixed $name
     * @return bool indicating if the offset exists for the dynamic subpath (Does not mean there is a instance. Use isset to check if the instance exists)
     * @throws NoResponseException
     */
    public function existsDynamicSub($name) {
        return $this->_dynamicSub->exists($name);
    }

    /**
     * @param mixed $offset
     * @return bool indicating if there is a instance for this offset
     */
    public function offsetExists($offset)
    {
        return $this->_dynamicSub->offsetExists($offset);
    }

    /**
     * @param mixed $offset
     * @return mixed instance for this offset
     */
    public function offsetGet($offset)
    {
        return $this->_dynamicSub->offsetGet($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->_dynamicSub->offsetSet($offset, $value);
    }

    /**
     * @param mixed $offset
     * deletes the instance at $offset
     */
    public function offsetUnset($offset)
    {
        $this->_dynamicSub->offsetUnset($offset);
    }
}