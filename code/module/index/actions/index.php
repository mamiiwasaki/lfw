<?php
/*-----------------------------------------------------------------------------
 *  Lightweight framework Rev 1.3
 *-----------------------------------------------------------------------------
 *  2009-10-31 : initial version by Hide
 *  2016-01-02 : rebuild
 *
 *  トップページ
 *----------------------------------------------------------------------------*/
require_once dirname(__FILE__) . '/../config.php';
require_once(dirname(__FILE__) . '/../config.php');

class app_main extends ActionBase
{
    // initialize
    public function initialize(dataContainer $data, userContainer $user, factory $factory)
    {
        $this->debug_query = false;
        $this->debug_status = false;
        $this->debug_echo = !true;
        return;
    }

    // dispatch
    public function dispatch(dataContainer $data, userContainer $user, factory $factory)
    {
    
        if($test=0){
            echo "jikjkjkj";
        }
        $this->viewitem['TEST'] = 'kdajglkadfjlsfjal';
        return;
    }
}
