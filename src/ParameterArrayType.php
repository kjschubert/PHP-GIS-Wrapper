<?php
namespace GISwrapper;

/**
 * Class ParameterArrayType
 * wrapper for parameters which are part of a array
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @package GISwrapper
 * @version 0.2
 */
class ParameterArrayType extends ParameterDefaultType implements \ArrayAccess, \Iterator, \Countable
{
    /**
     * @var array
     */
    private $_values;

    /**
     * @var int
     */
    private $_currentKey;

    /**
     * @var array
     */
    private $_currentKeys;

    /**
     * ParameterArrayType constructor.
     * @param array $cache parsed swagger file for this parameters
     * @param bool $strict true if this parameter is an array for all operations which it is part of
     */
    public function __construct($cache, $strict)
    {
        parent::__construct($cache);
        $this->_values = array();
        $this->_strict = $strict;
    }

    /**
     * @param mixed $offset
     * @return bool indicating if this offset is instantiated
     */
    public function offsetExists($offset)
    {
        return isset($this->_values[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed instance of this offset if there are subparameters or else the value at this offset
     */
    public function offsetGet($offset)
    {
        if(!isset($this->_values[$offset])) {
            $this->_values[$offset] = new ParameterDefaultType($this->_cache);
        }
        if($this->_values[$offset]->hasChilds() || $this->_values[$offset] instanceof ParameterArrayType) {
            return $this->_values[$offset];
        } else {
            return $this->_values[$offset]->value();
        }
    }

    /**
     * @param mixed $offset
     * @param mixed $value Parameter instance or value for the offset
     */
    public function offsetSet($offset, $value)
    {
        if($value instanceof ParameterDefaultType) {
            if(is_null($offset)) {
                $this->_values[] = $value;
            } else {
                $this->_values[$offset] = $value;
            }
        } else {
            if(is_null($offset)) {
                $val = new ParameterDefaultType($this->_cache);
                $val->value($value);
                $this->_values[] = $val;
            } else {
                if(!isset($this->_values[$offset])) {
                    $this->_values[$offset] = new ParameterDefaultType($this->_cache);
                }
                $this->_values[$offset]->value($value);
            }
        }
    }

    /**
     * @param mixed $offset offset of the instance to be destroyed
     */
    public function offsetUnset($offset)
    {
        unset($this->_values[$offset]);
    }

    /**
     * returns the instance at the current offset, or the value if there are no subparameters
     * @return mixed
     */
    public function current()
    {
        if($this->_values[$this->_currentKeys[$this->_currentKey]]->hasChilds() || $this->_values[$this->_currentKeys[$this->_currentKey]] instanceof ParameterArrayType) {
            return $this->_values[$this->_currentKeys[$this->_currentKey]];
        } else {
            return $this->_values[$this->_currentKeys[$this->_currentKey]]->value();
        }
    }

    /**
     * move to the next offset
     */
    public function next()
    {
        $this->_currentKey++;
    }

    /**
     * @return mixed key of the current offset
     */
    public function key()
    {
        return $this->_currentKeys[$this->_currentKey];
    }

    /**
     * Rewind the Iterator to the first element
     */
    public function rewind()
    {
        $this->_currentKey = 0;
        $this->_currentKeys = array_keys($this->_values);
    }

    /**
     * @return int number of elements of this parameter
     */
    public function count()
    {
        return count($this->_values);
    }

    /**
     * Checks if current position is valid if $operation is null and else if this parameter is valid for the http method
     * @param null|string $operation null or the http method
     * @return bool
     */
    public function valid($operation = null) {
        if($operation === null) {
            return isset($this->_currentKeys[$this->_currentKey]);
        } else {
            if(isset($this->_cache['operations'][$operation])) {
                if($this->_cache['operations'][$operation]['type'] == "Array") {
                    if($this->hasChilds()) {
                        foreach ($this->_values as $value) {
                            if (!$value->valid($operation)) {
                                return false;
                            }
                        }
                        return true;
                    } else {
                        if($this->_cache['operations'][$operation]['required'] && count($this->_values) == 0) {
                            return false;
                        }
                        foreach ($this->_values as $value) {
                            if(!is_scalar($value->value()) || $value->value() === null) {
                                return false;
                            }
                        }
                        return true;
                    }
                } else {
                    return parent::valid($operation);
                }
            } else {
                return true;
            }
        }
    }

    /**
     * @param string $operation http method
     * @return array|null|string value of the parameter for the specified http method
     */
    public function getRequestValue($operation)
    {
        if($this->_cache['operations'][$operation]['type'] == "Array") {
            $r = array();
            foreach ($this->_values as $key => $value) {
                $v = $value->getRequestValue($operation);
                if ($v !== null) {
                    $r[] = $v;
                }
            }
            if (count($r) > 0) {
                return $r;
            } else {
                return null;
            }
        } else {
            return parent::getRequestValue($operation);
        }
    }
}