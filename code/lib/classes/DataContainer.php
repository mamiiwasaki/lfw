<?php

/**
 * Lightweight framework Rev 1.3
 *-----------------------------------------------------------------------------
 * 2006-07-24 : initial version
 * 2015-05-12 : rebuild by hide
 * data container
 */
class DataContainer
{
    /** @var array<int,array<string,string>> */
    public $_attribute = [];
    /** @var array<int,array<string,string>> */
    public $_error_code = [];
    /** @var  (int|string)[] */
    public $_error_message = [];

    /**
     * @param string $name
     * @param array<int, string> $data
     */
    public function setAttribute($name, $data):void
    {
        $this->_attribute[$name] = $data;
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function getAttribute($key)
    {
        if (is_array($this->_attribute) ? !count($this->_attribute) : true) {
            return null;
        }
        if (func_num_args() == 1) {
            return array_key_exists($key, $this->_attribute) ? $this->_attribute[$key] : null;
        } else {
            logsave("DataContainer", "multiple argument request !!!!");
            return null;
        }
    }

    /**
     * check Attribute
     * @param string $name
     * @return bool
     */
    public function hasAttribute($name)
    {
        return isset($this->_attribute[$name]);
    }

    /**
     * remove Attribute
     * @param string $name
     */
    public function removeAttribute($name):void
    {
        if (isset($this->_attribute[$name])) {
            unset($this->_attribute[$name]);
        }
    }

    /**
     * clear Attribute
     */
    public function cleanAttributes():void
    {
        $this->_attribute = [];
    }

    /**
     * set post/get parameter to attribute
     * @param string|array $name
     * @param false $default_parameter
     * @param int $post_only
     * @return false|mixed
     */
    public function getParameter($name, $default_parameter = false, $post_only = 3)
    {
        if (func_num_args() == 5) {
            $get = func_get_arg(3);
            $post = func_get_arg(4);
        } else {
            $get = filter_input_array(1);
            $post = filter_input_array(0);
        }
        if (is_array($name)) {
            foreach ($name as $key => $value) {
                $gc = $this->getParameter(
                    $value,
                    false,
                    $post_only,
                    isset($get[$key]) ? $get[$key] : [],
                    isset($post[$key]) ? $post[$key] : []
                );
                return $gc;
            }
        }
        if (($post_only == 3 || $post_only == 1) && isset($post[$name])) {
            return $post[$name];
        } elseif (($post_only == 3 || $post_only == 2) && isset($get[$name])) {
            return $get[$name];
        } else {
            return $default_parameter;
        }
    }

    /**
     * check parameter
     * @param string $name
     * @param int $post_only
     * @return bool
     */
    public function hasParameter($name, $post_only = 3)
    {
        return $this->getParameter($name, false, $post_only) !== false;
    }

    /**
     * set error
     * @param string $name
     * @param string $validator
     * @param int|string $code
     * @param string $message
     */
    public function setError($name, $validator, $code, $message):void
    {
        $this->_error_code[$name][$validator] = $code;
        $this->_error_message[$name][$validator] = $message;
    }

    /**
     * @return mixed
     */
    public function getErrors()
    {
        $i = 0;
        foreach ($this->_error_message as $key => $values) {
            foreach ($values as $id => $value) {
                $res["message"][$i] = $value;
                $res["code"][$i] = $this->_error_code[$key][$id];
                $res["keys"][$key][] = $i;
                $i++;
            }
        }
        $res["count"] = $i;
        return $res;
    }

    /**
     * get error by parameter
     * @param string $name
     * @return array|null
     */
    public function getError($name)
    {
        return isset($this->_error_message[$name]) ? [
            "message" => $this->_error_message[$name],
            "code" => $this->_error_code[$name]
        ] : null;
    }

    /**
     * get error status
     * @return bool
     */
    public function hasError()
    {
        return (count($this->_error_code) + count($this->_error_message)) > 0;
    }

    /**
     * return clone
     * @return array<int, array<int, array<string, string>>>
     */
    public function dataClone()
    {
        return [
            $this->_attribute,
            $this->_error_code,
            $this->_error_message,
        ];
    }

    /**
     * replace data container
     * @param  (int|string)[] $data
     */
    public function dataReplace(&$data):void
    {
        $this->_attribute = $data[0];
        $this->_error_code = $data[1];
        $this->_error_message = $data[2];
    }

    /**
     * clear error
     */
    public function errorFree():void
    {
        $this->_error_code = [];
        $this->_error_message = [];
    }
}
