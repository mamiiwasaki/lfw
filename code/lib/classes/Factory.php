<?php

/*-----------------------------------------------------------------------------
 *  Lightweight framework Rev 1.3
 *-----------------------------------------------------------------------------
 *  2006-07-24 : initial version
 *  2015-05-12 : rebuild by hide
 *
 *
 *  factory class
 *----------------------------------------------------------------------------*/

class Factory
{
    public $data;
    public $user;
    public $request_module;
    public $request_action;
    public $_chain = [];

    /*--------------------------------------------------------------------------
     * constructor
     *-------------------------------------------------------------------------*/
    public function __construct(dataContainer &$data, userContainer &$user)
    {
        $this->data = $data;
        $this->user = $user;
    }

    /*--------------------------------------------------------------------------
     * create
     *-------------------------------------------------------------------------*/
    public function create($action, $module = false)
    {
        if ($module === false) {
            $module = $this->request_module;
        }

        include_once MODULE_DIR . $module . ACTION_DIR_NAME . $action . ".php";
        $action = new $action();
        return $action;
    }

    /*--------------------------------------------------------------------------
     * forward
     *-------------------------------------------------------------------------*/
    public function forward($action, $module = false)
    {
        $action = $this->create($action, $module);
        $action->initialize($this->data, $this->user, $this);
        $action->dispatch($this->data, $this->user, $this);
    }

    /*--------------------------------------------------------------------------
     * chain
     *-------------------------------------------------------------------------*/
    public function chain($name, $action, $module = false)
    {
        $this->_chain[] = [$name, [$action, $module]];
    }

    /*--------------------------------------------------------------------------
     * go
     *-------------------------------------------------------------------------*/
    public function go()
    {
        $res = [];
        $data = clone $this->data;
        foreach ($this->_chain as $value) {
            $this->data = $data;
            ob_start();
            $this->forward($value[1][0], $value[1][1]);
            $res[$value[0]] = ob_get_contents();
            ob_clean();
        }
        return $res;
    }
}
