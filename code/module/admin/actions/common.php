<?php
/*-----------------------------------------------------------------------------
 *  Lightweight framework Rev 1.3
 *-----------------------------------------------------------------------------
 * 2009-10-31 : initial version by Hide
 * 2015-05-12 : rebuild
 *
 * admin / common
 *----------------------------------------------------------------------------*/
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "config.php");

class app_main extends ActionBase
{
    /**
     * @param dataContainer $data
     * @param userContainer $user
     * @param factory $factory
     */
    public function initialize(dataContainer $data, userContainer $user, factory $factory)
    {
        $this->debug_echo = false;
        $this->debug_status = false;
        return;
    }

    /**
     * @param dataContainer $data
     * @param userContainer $user
     * @param factory $factory
     * @return bool|void
     */
    public function dispatch(dataContainer $data, userContainer $user, factory $factory)
    {
        echo("app1 / common");
        return;
    }
}
