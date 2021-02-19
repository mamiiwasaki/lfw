<?php
/*-----------------------------------------------------------------------------
 *	Lightweight framework Rev 1.3
 *-----------------------------------------------------------------------------
 *	2006-07-24 : initial version
 *	2015-05-12 : rebuild by hide
 *	2014-09-10 : rebuild
 *
 *	rebuild
 *----------------------------------------------------------------------------*/
require_once CLASSES_DIR . 'dao_util.trait.php';

class test_dao extends DBIO
{
    use daoUtilTrait;
    public $tablename = "users_user";

    // getAll
    public function getAllData($term_arr = null)
    {
        $sql = "select * from {$this->tablename}";
        echo $sql;
        pr($this);
        pr($this->getAll($sql));
        echo count($this->getAll($sql));
        return $this->getAll($sql);
    }

    // getAllSearch
    public function getAllSearch($key, $value, $term_arr = null)
    {
        $prm[] = '%' . $value . '%';

        $term = " where $key like ? and uid is not null ";
        if (is_array($term_arr)) {
            foreach ($term_arr as $key => $value) {
                $compare = ($key == "start") ? ">" : "<";

                $term .= " and registtime $compare ?";
                $prm[] = date('Y-m-d', $value);;
            }
        }
        $sql = "select * from {$this->tablename} $term order by registtime desc";
        return $this->getAll($sql, $prm);
    }

    // getAllSearch
    public function getAllSearchByUid($uid_list, $term_arr = null)
    {
        $uids = "";
        $prm = [];
        for ($i = 0; $i < count($uid_list); ++$i) {
            if ($uids != "") {
                $uids .= " or";
            }
            $uids .= " uid = ?";
            $prm[] = $uid_list[$i];
        }
        $term = " where ($uids)";
        if (is_array($term_arr)) {
            foreach ($term_arr as $key => $value) {
                $compare = ($key == "start") ? ">" : "<";

                $term .= " and registtime $compare ?";
                $prm[] = date('Y-m-d', $value);;
            }
        }
        $sql = "select * from {$this->tablename} $term order by registtime desc";
        return $this->getAll($sql, $prm);
    }

    // getByUID
    public function getByUID($uid)
    {
        $sql = "select * from {$this->tablename} where uid=? order by registtime desc";
        return $this->getAll($sql, array($uid));
    }

    // ハッシュ値から取得
    public function getByHash($hash)
    {
        $sql = "select * from {$this->tablename} where hash =?";
        return $this->getRow($sql, array($hash));
    }

    // 変更
    public function update_count($uid, $id)
    {
        $now = date("Y-m-d H:i:s");
        $sql
            = "update {$this->tablename} set count = count+1, updatetime =? where uid =? and report_id =?";
        $prm = array($now, $uid, $id);
        return $this->query($sql, $prm);
    }
}
