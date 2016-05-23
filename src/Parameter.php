<?php
/**
 * Created by PhpStorm.
 * User: kjs
 * Date: 23.05.16
 * Time: 15:06
 */

namespace GISwrapper;


class Parameter
{
    private $_subparams;
    private $_cache;
    private $_value;

    function __construct($cache)
    {
        $this->_cache = $cache;
        $this->_subparams = array();
    }

    /**
     * @param $name
     * @return null|mixed
     */
    public function __get($name) {
        if(array_key_exists($name, $this->_cache['subparams'])) {
            if(!isset($this->_subparams[$name])) {
                $this->_subparams[$name] = new Parameter($this->_cache['subparams'][$name]);
            }
            if($this->_subparams[$name]->hasChilds()) {
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
     * @param $name Name of the child parameter
     * @param $value Sets the child $name to $value if it is an object or sets the value of $name to $value if $value is not a object
     */
    public function __set($name, $value) {
        if(array_key_exists($name, $this->_cache['subparams'])) {
            if($value instanceof Parameter) {
                $this->_subparams[$name] = $value;
            } else {
                if(!isset($this->_subparams[$name])) {
                    $this->_subparams[$name] = new Parameter($this->_cache['subparams'][$name]);
                }
                $this->_subparams[$name]->set($value);
            }
        }
    }

    /**
     * @param mixed|null $value Sets the value of this parameter if $value is not null
     * @return mixed|null
     */
    public function value($value = null) {
        if(!$this->hasChilds()) {
            if ($value !== null) {
                if($this->_cache['type'] == "Array" && !is_array($value)) {
                    $this->_value = array($value);
                } else {
                    $this->_value = $value;
                }
            }
            return $this->_value;
        } else {
            trigger_error("Can not set or get value of Parameter with subparameters", E_USER_ERROR);
        }
    }

    /**
     * @return mixed|null The value of this parameter
     */
    public function get() {
        if(!$this->hasChilds()) {
            return $this->value();
        } else {
            trigger_error("Can not get value of Parameter with subparameters", E_USER_ERROR);
        }
    }

    /**
     * @param $value Sets the value of this parameter
     */
    public function set($value) {
        if(!$this->hasChilds()) {
            $this->value($value);
        } else {
            trigger_error("Can not set value of Parameter with subparameters", E_USER_ERROR);
        }
        $this->value($value);
    }

    /**
     * @return bool
     */
    public function hasChilds() {
        return (count($this->_cache['subparams']) > 0);
    }

    /**
     * @param $operation
     * @return bool
     */
    public function required($operation) {
        if(in_array($operation, $this->_cache['operations'])) {
            return $this->_cache['operations'][$operation]['required'];
        } else {
            return false;
        }
    }

    /**
     * @param $operation
     * @return bool
     */
    public function valid($operation) {
        if(in_array($operation, $this->_cache['operations'])) {
            if($this->_cache['operations'][$operation]['required']) {
                if($this->hasChilds()) {
                    foreach($this->_cache['subparams'] as $name => $subparam) {
                        if($subparam['required'] && (!isset($this->_subparams[$name]) || !$this->_subparams[$name]->valid($operation))) {
                            return false;
                        }
                    }
                    return true;
                } else {
                    if($this->_value != null) return true;
                }
            } else {
                return true;
            }
        } else {
            return true;
        }
    }

    /**
     * resets the parameter and its children
     */
    public function reset() {
        $this->_value = null;
        $this->_subparams = array();
    }

    /**
     * @param $operation
     * @return array|null|string
     */
    public function getRequestValue($operation) {
        if($this->hasChilds()) {
            $r = array();
            foreach($this->_cache['subparams'] as $name => $param) {
                if(in_array($operation, $param['operations'])) {
                    $v = $this->_subparams[$name]->getRequestValue($operation);
                    if($v !== null) {
                        $r[$name] = $v;
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