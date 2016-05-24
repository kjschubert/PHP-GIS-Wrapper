<?php
/**
 * Created by PhpStorm.
 * User: kjs
 * Date: 24.05.16
 * Time: 14:54
 */

namespace GISwrapper;


class APIEndpointPagedDynamicSub extends APIEndpointPaged implements \ArrayAccess
{

    private $_dynamicSub;

    public function __construct($cache, $auth, $pathParams = array())
    {
        parent::__construct($cache, $auth, $pathParams);
        $this->_dynamicSub = new DynamicSub($cache, $auth, $pathParams);
    }

    public function offsetExists($offset)
    {
        return $this->_dynamicSub->offsetExists($offset);
    }

    public function offsetGet($offset)
    {
        return $this->_dynamicSub->offsetGet($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->_dynamicSub->offsetSet($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->_dynamicSub->offsetUnset($offset);
    }

    public function reset() {
        $this->_dynamicSub->reset();
        parent::reset();
    }
}