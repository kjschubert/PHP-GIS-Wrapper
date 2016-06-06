<?php
namespace GISwrapper;

/**
 * Class ParameterDefaultType
 * representing a parameter or element of a array parameter
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @package GISwrapper
 * @version 0.2
 */
class ParameterDefaultType
{
    /**
     * @var array
     */
    protected $_subparams;

    /**
     * @var array
     */
    protected $_cache;

    /**
     * @var mixed
     */
    protected $_value;

    /**
     * @var bool
     */
    protected $_strict;

    /**
     * ParameterDefaultType constructor.
     * @param array $cache parsed swagger file for this parameter
     */
    function __construct($cache)
    {
        $this->_cache = $cache;
        $this->_subparams = array();
        $this->_strict = false;
    }

    /**
     * @param mixed $name name of the subparameter
     * @return null|mixed property value or instance
     */
    public function __get($name) {
        if(array_key_exists($name, $this->_cache['subparams'])) {
            if(!isset($this->_subparams[$name])) {
                $this->_subparams[$name] = ParameterFactory::factory($this->_cache['subparams'][$name]);
            }
            if($this->_subparams[$name]->hasChilds() || $this->_subparams[$name] instanceof ParameterArrayType) {
                return $this->_subparams[$name];
            } else {
                return $this->_subparams[$name]->get();
            }
        } else {
            trigger_error("Property " . $name . " does not exists", E_USER_WARNING);
            return null;
        }
    }

    /**
     * @param $name Name of the subparameter
     * @param $value Sets the child $name to $value if it is an object or sets the value of $name to $value if $value is not a object
     */
    public function __set($name, $value) {
        if(array_key_exists($name, $this->_cache['subparams'])) {
            if($value instanceof ParameterDefaultType || is_subclass_of($value, ParameterDefaultType::class)) {
                $this->_subparams[$name] = $value;
            } else {
                if(!isset($this->_subparams[$name])) {
                    $this->_subparams[$name] = ParameterFactory::factory($this->_cache['subparams'][$name]);
                }
                if(is_scalar($value) || $value instanceof \DateTime) {
                    $this->_subparams[$name]->value($value);
                } elseif(is_array($value)) {
                    // recreate subparam to reset other keys and keep scalar value if existent
                    $v = ((isset($this->_subparams[$name]) && is_scalar($this->_subparams[$name]->value())) ? $this->_subparams[$name]->value() : null);
                    $this->_subparams[$name] = ParameterFactory::factory($this->_cache['subparams'][$name]);
                    $this->_subparams[$name]->value($v);

                    //proceed array
                    if($this->_subparams[$name] instanceof ParameterArrayType) {
                        foreach($value as $key => $v) {
                            $this->_subparams[$name]->offsetSet($key, $v);
                        }
                    } else {
                        foreach($value as $key => $v) {
                            $this->_subparams[$name]->$key = $v;
                        }
                    }
                } else {
                    trigger_error("Invalid value for property " . $name, E_USER_ERROR);
                }
            }
        } else {
            trigger_error("Property " . $name . " does not exist.", E_USER_WARNING);
        }
    }

    /**
     * convert this parameter to a string
     * @return string
     */
    public function __toString()
    {
        if($this->hasChilds()) {
            return "ArrayParameter";
        } else {
            return strval($this->_value);
        }
    }

    /**
     * @param mixed $name property name of the instance to be destroyed
     */
    public function __unset($name) {
        if(isset($this->_subparams[$name])) {
            unset($this->_subparams[$name]);
        }
    }

    /**
     * @param $name property name
     * @return bool indicating if the property is instantiated
     */
    public function __isset($name) {
        return isset($this->_subparams[$name]);
    }

    /**
     * @param $name property name
     * @return bool indicating if the property exists (Does not mean it is instantiated. Use isset for this)
     */
    public function exists($name) {
        return isset($this->_cache['subparams'][$name]);
    }

    /**
     * @param mixed|null $value sets the value of this parameter if $value is not null
     * @return mixed|null
     */
    public function value($value = null) {
        if($this->hasChilds()) {
            if($value === null) {
                $r = array();
                foreach($this->_subparams as $name => $param) {
                    $v = $param->get();
                    if($v !== null) {
                        $r[$name] = $v;
                    }
                }
                if(count($r) > 0) {
                    return $r;
                } else {
                    return null;
                }
            } else {
                if(is_array($value)) {
                    $this->_subparams = array();
                    foreach($value as $key => $v) {
                        if(array_key_exists($key, $this->_cache['subparams'])) {
                            if(!isset($this->_subparams[$key])) {
                                $this->_subparams[$key] = ParameterFactory::factory($this->_cache['subparams'][$key]);
                            }
                            $this->_subparams[$key]->value($v);
                        } else {
                            trigger_error("Property " . $key . " does not exist", E_USER_ERROR);
                        }
                    }
                } else {
                    trigger_error("Can not set value of Parameter with subparameters", E_USER_ERROR);
                }
            }
        } else {
            if ($value !== null) {
                if(is_scalar($value) || $value instanceof \DateTime) {
                    if($this->_strict) {
                        trigger_error("Can not set a scalar value to a Parameter which is in all operations an Array.", E_USER_ERROR);
                    } else {
                        $this->_value = $value;
                    }
                } else {
                    trigger_error("Property value must be scalar.", E_USER_ERROR);
                }
            }
            return $this->_value;
        }
    }

    /**
     * @return mixed|null The value of this parameter
     */
    public function get() {
        return $this->value();
    }

    /**
     * @param $value Sets the value of this parameter
     */
    public function set($value) {
        $this->value($value);
    }

    /**
     * @return bool indicating if this parameter has subparameters
     */
    public function hasChilds() {
        return (count($this->_cache['subparams']) > 0);
    }

    /**
     * @param $operation http method to check for
     * @return bool
     * @throws InvalidParameterTypeException
     */
    public function valid($operation) {
        if(array_key_exists($operation, $this->_cache['operations'])) {
            switch($this->_cache['operations'][$operation]['type']) {
                case 'Integer':
                    if(is_int($this->_value)) {
                        return true;
                    } else {
                        return false;
                    }
                    break;

                case 'String':
                    if(is_string($this->_value)) {
                        return true;
                    } else {
                        return false;
                    }
                    break;

                case 'Date':
                case 'DateTime':
                    if($this->_value instanceof \DateTime) {
                        return true;
                    } else {
                        return false;
                    }
                    break;

                case 'Virtus::Attribute::Boolean':
                    if(is_bool($this->_value)) {
                        return true;
                    } else {
                        return false;
                    }
                    break;

                case 'Hash':
                    foreach($this->_cache['subparams'] as $name => $subparam) {
                        if(isset($this->_subparams[$name])) {
                            if(!$this->_subparams[$name]->valid($operation)) return false;
                        } elseif(isset($subparam['operations'][$operation]) && $subparam['operations'][$operation]['required']) {
                            return false;
                        }
                    }
                    return true;
                    break;

                case 'Array':
                    if($this->hasChilds()) {
                        foreach($this->_cache['subparams'] as $name => $subparam) {
                            if(isset($this->_subparams[$name])) {
                                if(!$this->_subparams[$name]->valid($operation)) return false;
                            } elseif(isset($subparam['operations'][$operation]) && $subparam['operations'][$operation]['required']) {
                                return false;
                            }
                        }
                        return true;
                    } else {
                        return false;
                    }
                    break;

                default:
                    throw new InvalidParameterTypeException("Can not handle parameter type " . $this->_cache['operations'][$operation]['type']);
            }
        } else {
            return true;
        }
    }

    /**
     * @param $operation http method
     * @return array|null|mixed value of this parameter for the specified http method
     */
    public function getRequestValue($operation) {
        if($this->hasChilds()) {
            $r = array();
            foreach($this->_cache['subparams'] as $name => $param) {
                if(array_key_exists($operation, $param['operations'])) {
                    if(isset($this->_subparams[$name])) {
                        $v = $this->_subparams[$name]->getRequestValue($operation);
                        if($v !== null) {
                            $r[$name] = $v;
                        }
                    }
                }
            }
            if(count($r) > 0) {
                return $r;
            } else {
                return null;
            }
        } else {
            return $this->_value;
        }
    }
}