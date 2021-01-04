<?php
/*-----------------------------------------------------------------------------
 * Lightweight framework Rev 1.3
 *-----------------------------------------------------------------------------
 * 2009-10-31 : initial version by Hide
 * 2015-05-12 : rebuild
 *
 * index / index
 *----------------------------------------------------------------------------*/
require_once(dirname(__FILE__) . "/../config.php");

class app_main extends ActionBase
{
    // initialize
    public function initialize(dataContainer $data, userContainer $user, factory $factory)
    {
        $this->debug_echo = false;
        $this->debug_status = false;
        return;
    }

    // dispatch
    public function dispatch(dataContainer $data, userContainer $user, factory $factory)
    {
        $posts = filter_input_array(INPUT_POST);
        $this->viewitem['TEXT'] = implode("<br>", $posts);

        return;
    }
}
