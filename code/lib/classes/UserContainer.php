<?php
/**-----------------------------------------------------------------------------
 *  Lightweight framework Rev 1.1
 *-----------------------------------------------------------------------------
 *  2006-07-24 : initial version
 *  2009-10-31 : rebuild
 *  2011-05-10 : rebuild by hide
 *  2015-05-12 : rebuild by hide
 *
 *  user information
 *----------------------------------------------------------------------------*/

class UserContainer
{
    /** @var bool */
    public $is_login = false;
    /** @var string */
    public $user_name;
    /** @var bool */
    public $is_admin_login = false;
    /** @var integer */
    public $admin_auth;
    /** @var string */
    public $emp_no;
    /** @var string */
    public $admin_user_name;

    /**
     * login
     * @param string|int $account
     * @param string $password
     * @return bool
     */
    public function login($account, $password)
    {
        $this->admin_logout();
        // get data
        $res = ORM::for_table('t_user')->where(['account' => $account, 'status' => '1', 'del_flg' => '0'])->find_one();
        if (empty($res)) {
            return false;
        }
        // check password
        if (!$this->passwordVerify($password, $res->password)) {
            return false;
        }
        $this->is_login = true;
        $this->user_name = $res->name;
        return true;
    }

    /** logout
     */
    public function logout(): void
    {
        // 初期化
        $this->initial();
        $this->is_login = false;
    }

    /**
     * admin login
     * @param string|int $emp_no
     * @param string $password
     * @return bool
     */
    public function admin_login($emp_no, $password)
    {
        $this->admin_logout();
        // get data
        $res = ORM::for_table('t_admin_user')->where(['emp_no' => $emp_no, 'status' => '1', 'del_flg' => '0'])->find_one();
        if (empty($res)) {
            return false;
        }
        // check password
        if (!$this->passwordVerify($password, $res->password)) {
            return false;
        }
        $this->is_admin_login = true;
        $this->admin_auth = $res->auth;
        $this->emp_no = $res->emp_no;
        return true;
    }

    /** admin logout
     */
    public function admin_logout(): void
    {
        // 初期化
        $this->initial();
        $this->is_admin_login = false;
    }

    /**
     * 全てのプロパティーを初期化
     */
    public function initial(): void
    {
        global $data;
        $data->cleanAttributes();
        foreach ($this as $key => $value) {
            $this->$key = null;
        }
    }

    /**
     * crypt
     * @param string $s
     * @return false|string|null
     */
    function crypt($s)
    {
        return password_hash($s, PASSWORD_DEFAULT);
    }

    /**
     * password check
     * @param string $plain
     * @param string $hash
     * @return bool
     */
    function passwordVerify($plain, $hash)
    {
        return password_verify($plain, $hash);
    }
}
