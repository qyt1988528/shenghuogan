<?php
namespace MDK;
class MObject implements \ArrayAccess, \Countable
{
    public function __construct($array = []) {
        foreach ($array as $key => $value) {
            $this->offsetSet($key, $value);
        }
    }

    public function offsetUnset($index) {
        $index = strval($index);
        $this->{$index} = null;
    }

    public function offsetSet($index, $value) {
        $index = strval($index);
        if (is_array($value)) {
            $this->{$index} = new self($value);
        } else {
            $this->{$index} = $value;
        }
    }

    public function offsetGet($index) {
        $index = strval($index);
        return $this->{$index};
    }

    public function offsetExists($index) {
        $index = strval($index);
        return isset($this->{$index});
    }

    public function count() {
        return count(get_object_vars($this));
    }

    public function toArray() {
        $array = [];
        foreach (get_object_vars($this) as $key => $value) {
            if (is_object($value)) {
                if (method_exists($value, 'toArray')) {
                    $array[$key] = $value->toArray();
                } else {
                    $array[$key] = $value;
                }
            } else {
                $array[$key] = $value;
            }
        }
        return $array;
    }

    public function get($index, $defaultValue = null) {
        $index = strval($index);
        if (isset($this->{$index})) {
            return $this->{$index};
        }
        return $defaultValue;
    }

    public function __get($name) {
        return $this->offsetExists($name) ? $this->offsetGet($name) : null;
    }
}