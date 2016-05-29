<?php
/**
 * Created by PhpStorm.
 * User: kjs
 * Date: 24.05.16
 * Time: 19:20
 */

namespace GISwrapper;


class ParameterArrayType extends ParameterDefaultType implements \ArrayAccess, \Iterator, \Countable
{
    private $_values;
    private $_currentKey;
    private $_currentKeys;
    
    public function __construct($cache, $strict)
    {
        parent::__construct($cache);
        $this->_values = array();
        $this->_strict = $strict;
    }

    public function offsetExists($offset)
    {
        return isset($this->_values[$offset]);
    }

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

    public function offsetUnset($offset)
    {
        unset($this->_values[$offset]);
    }

    public function current()
    {
        if($this->_values[$this->_currentKeys[$this->_currentKey]]->hasChilds() || $this->_values[$this->_currentKeys[$this->_currentKey]] instanceof ParameterArrayType) {
            return $this->_values[$this->_currentKeys[$this->_currentKey]];
        } else {
            return $this->_values[$this->_currentKeys[$this->_currentKey]]->value();
        }
    }

    public function next()
    {
        $this->_currentKey++;
    }

    public function key()
    {
        return $this->_currentKeys[$this->_currentKey];
    }

    public function rewind()
    {
        $this->_currentKey = 0;
        $this->_currentKeys = array_keys($this->_values);
    }

    public function count()
    {
        return count($this->_values);
    }

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