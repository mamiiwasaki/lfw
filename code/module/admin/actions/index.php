<?php
/*-----------------------------------------------------------------------------
 *  Lightweight framework Rev 1.3
 *-----------------------------------------------------------------------------
 * 2009-10-31 : initial version by Hide
 * 2015-05-12 : rebuild
 *
 * admin / index
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
        $posts = filter_input_array(INPUT_POST);
        if ($posts['account'] == 'admin' && $posts['password'] == 'password') {
            header('Location: main.html');
            exit;
        } else {
            $message = 'アカウントまたはパスワードが一致しません。';
        }
        $this->viewitem['MESSAGE'] = $message;
        return;
    }
}
