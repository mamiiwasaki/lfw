<?php

/*-----------------------------------------------------------------------------
 *  Lightweight framework Rev 1.1
 *-----------------------------------------------------------------------------
 *	2006-07-24 : initial version
 *
 *  template Trait
 *----------------------------------------------------------------------------*/

trait daoUtilTrait
{
    // -------------------------------------------------------------------------
    //	データ取得
    // -------------------------------------------------------------------------
    public function get($id = null, $table_name = '', $pkey_name = '', $orderby = '', $where_str = '')
    {
        // table_name, pkey_name
        if (empty($table_name)) {
            $table_name = $this->table_name;
        }
        if (empty($pkey_name)) {
            $pkey_name = $this->pkey_name;
        }

        if ($id != null) {
            $sql = "SELECT * FROM $table_name WHERE $pkey_name=?";
            //sql_log($sql, array($id), 'dao get');
            return $this->getRowAssoc($sql, array($id));
        }
        // all
        $sql = "select * from $table_name ";
        if (!empty($sql)) {
            $sql .= $where_str;
        }
        if ($orderby !== '') {
            $sql .= "order by " . $orderby;
        }
        //sql_log($sql, 'dao get');
        return $this->getAllAssoc($sql);
    }

    // -------------------------------------------------------------------------
    //	データの登録・更新
    // -------------------------------------------------------------------------
    public function save($post, $table_name = '', $pkey_name = '')
    {
        //--------------------------------------------------
        // table_name, pkey_name
        if (empty($table_name)) {
            $table_name = $this->table_name;
        }
        if (empty($pkey_name)) {
            $pkey_name = $this->pkey_name;
        }
        $pkey = (!empty($post[$pkey_name])) ? $post[$pkey_name] : '';
        // DB参照して既存がないかチェックする
        $insert_flg = false;
        if ($pkey_name !== 'id') {
            $cnt = $this->getDataOne([$pkey_name => $pkey], "count(*)", $table_name);
            if (empty($cnt)) {
                $insert_flg = true;
            }
        } else {
            if (empty($pkey)) {
                $insert_flg = true;
            }
        }

        // datetime
        $now = date("Y-m-d H:i:s");
        $post["updatetime"] = $now;
        if ($insert_flg) {
            $post["registtime"] = $now;
        }

        // カラム名を取得
        $fields_arr = $this->getFields($table_name);
        $set_key = [];
        $save_data = [];

        // テーブルにないカラムはスルーする。
        foreach ($post as $k => $v) {
            if (empty($pkey) && $k === $pkey_name) {
                continue;
            }
            if (array_key_exists($k, $fields_arr)) {
                $save_data[$k] = $v;
                $set_key[] = "$k=?";
            }
        }
        // SQL
        if ($insert_flg) {
            ///////////////////////
            // insert
            $sql = "insert into {$table_name} set " . implode(",", $set_key);
            //sql_log($sql, array_values($save_data), "dao insert");

        } else {
            ///////////////////////
            // update
            $sql = "update {$table_name} set " . implode(",", $set_key) . " where " . $pkey_name . "='" . $pkey . "'";
            //sql_log($sql, array_values($save_data), "dao update");
        }

        // query
        $this->query($sql, $save_data);

        // insertの場合、IDを取得
        if ($insert_flg) {
            $pkey = $this->getOne("select LAST_INSERT_ID();");
        }
        // IDを返す
        return $pkey;
    }

    // 単純なinsert
    function insert($post, $table_name = '')
    {
        if (empty($table_name)) {
            $table_name = $this->table_name;
        }
        // カラム名を取得
        $fields_arr = $this->getFields($table_name);
        $set_key = [];
        $save_data = [];

        // テーブルにないカラムはスルーする。
        foreach ($post as $k => $v) {
            if (array_key_exists($k, $fields_arr)) {
                $save_data[$k] = $v;
                $set_key[] = "$k=?";
            }
        }
        $sql = "insert into {$table_name} set " . implode(",", $set_key);
        //sql_log($sql, $save_data);
        $this->query($sql, $save_data);
    }

    // -------------------------------------------------------------------------
    // データの削除（フラグ）
    // -------------------------------------------------------------------------
    public function delete($id, $table_name = '', $pkey_name = '')
    {
        // table_name, pkey_name
        if (empty($table_name)) {
            $table_name = $this->table_name;
        }
        if (empty($pkey_name)) {
            $pkey_name = $this->pkey_name;
        }
        $sql_sub = '';
        $fields = $this->getFields($table_name);
        if (!empty($fields['updatetime'])) {
            $sql_sub .= ", updatetime=CURDATE()";
        }

        $sql = "update {$table_name} set del_flg=1 {$sql_sub} where $pkey_name=?";
        //sql_log($sql, [$id], 'dao delete');
        $this->query($sql, [$id]);
    }

    // -------------------------------------------------------------------------
    // 既存データがあるか
    // -------------------------------------------------------------------------
    public function isExists($colname, $code, $id = null)
    {
        if (empty($id)) {
            // 登録
            $sql = "select $this->pkey_name from $this->table_name where $colname=?";
            $prm = [$code];
        } else {
            // 更新
            $sql = "select $this->pkey_name from $this->table_name where $colname=? and ID<>?";
            $prm = [$code, $id];
        }
        if ($this->hasField($this->table_name, 'del_flg')) {
            $sql .= " and del_flg=0";
        }
        $res = $this->getOne($sql, $prm);
        return (!empty($res)) ? true : false;
    }
    // -------------------------------------------------------------------------
    //	１つのカラムを返す
    // -------------------------------------------------------------------------
    public function getName($colname, $val, $key = "", $table_name = "")
    {
        if (empty($key)) {
            $key = $this->pkey_name;
        }
        if (empty($table_name)) {
            $table_name = $this->table_name;
        }

        $sql = "select {$colname} from {$table_name} ";

        if (!is_array($key)) {
            $sql .= "where $key=?";
            $prm = array($val);
        } else {
            // 配列
            $tmp = array();
            foreach ($key as $keyname) {
                $tmp[] = $keyname . "=?";
            }
            $sql .= "where " . implode(" and ", $tmp);
            $prm = $val;
        }
        //sql_log($sql, $prm, "dao getName");
        return $this->getOne($sql, $prm);
    }

    /*
    * チェックボックスで「未選択」がある場合の処理
    */
    public function setSearchUnk($search_data, $colname, &$search_key, &$search_val)
    {
        if (!is_array($search_data)) {
            return;
        }    // 必ず配列でくるはず
        $tmpa = array();
        if (!empty($search_data)) {
            foreach ($search_data as $state) {
                if ($state === "unk") {
                    $tmpa[] = " ( $colname = ? OR $colname IS NULL ) ";
                    $search_val[] = "";
                } else {
                    if ($state != "") {
                        $tmpa[] = " $colname = ? ";
                        $search_val[] = $state;
                    }
                }
            }
        }
        if (!empty($tmpa)) {
            $search_key[] = "(" . implode(" OR ", $tmpa) . ")";
        } else {
            //$search_key[] = "false"; // 何もチェックされていなかったら何も表示しない
        }
    }

    /**
     * 配列で返す
     */
    public function getArray($table_name = null, $fields = array('id' => 'id', 'name' => 'name'), $where_arr = null)
    {
        if (empty($table_name)) {
            $table_name = $this->table_name;
        }
        $sql = "select id, name from $table_name where del_flg=0 ";
        $prm = array();

        // 条件
        if (!empty($where_arr)) {
            foreach ($where_arr as $val) {
                $operator = (!empty($val['operator'])) ? $val['operator'] : '=';
                $sql .= " and {$val['key']} {$operator} ?";
                $prm[] = $val['val'];
            }
        }
        $sql .= " order by sort";
        //sql_log($sql, $prm, 'dao getArray');
        $arr = array();
        $all = $this->getAllAssoc($sql, $prm);
        if (!empty($all)) {
            foreach ($all as $val) {
                $arr[$val['id']] = $val['name'];
            }
        }
        return $arr;
    }

    public function getArrayAll($table_name = null, $where_arr = null)
    {
        if (empty($table_name)) {
            $table_name = $this->table_name;
        }
        $sql = "select id, name from $table_name order by sort";
        $prm = array();
        // 条件
        if (!empty($where_arr)) {
            foreach ($where_arr as $val) {
                $operator = (!empty($val['operator'])) ? $val['operator'] : '=';
                $sql .= " where {$val['key']} {$operator} ?";
                $prm[] = $val['val'];
            }
        }
        $arr = array();
        $all = $this->getAllAssoc($sql, $prm);
        if (!empty($all)) {
            foreach ($all as $val) {
                $arr[$val['id']] = $val['name'];
            }
        }
        return $arr;
    }

    // 最古値を取得
    public function getOldest($table_name, $colname)
    {
        return $this->getOne("SELECT MIN({$colname}) FROM {$table_name}");
    }
    // -------------------------------------------------------------------------
    // ソート番号取得
    // -------------------------------------------------------------------------
    public function getNewSort($search_arr = [], $table_name = '')
    {
        if (empty($table_name)) {
            $table_name = $this->table_name;
        }

        $sql = "select max(sort)+1 from $table_name";
        $search_arr['del_flg'] = '0';
        list($search_keys, $prm) = $this->getConditions($search_arr);

        //sql_log($sql.$search_keys, $prm, 'dao getNewSort');
        $sort = $this->getOne($sql.$search_keys, $prm);
        return (!empty($sort)) ? $sort : 1;
    }
    // -------------------------------------------------------------------------
    // ソート更新
    // -------------------------------------------------------------------------
    /**
     * @param array $search_arr
     * del_flg がある前提で。
     */
    public function updateSort($search_arr = [])
    {
        // prm
        $id = filter_input(1, 'id');
        $sort = filter_input(1, 'sort');
        $sort_to = filter_input(1, 'sort_to');

        if ($sort > $sort_to) {
            //---------------------------------------
            // 小さくする
            //---------------------------------------
            // 自分がなりたいsortより後ろのソートたちを+1
            $this->query("update {$this->table_name} set sort=(sort+1) where sort >=?", [$sort_to]);

        } else {
            //---------------------------------------
            // 大きくする
            //---------------------------------------
            // 自分のsortからなりたいsortまで -1
            $sql = "update {$this->table_name} set sort=(sort-1) where sort between ? and ?";
            $this->query($sql, [$sort, $sort_to]);
        }
        // 自分をなりたいsortに
        $this->query("update {$this->table_name} set sort=? where id=?", [$sort_to, $id]);

        // del_flg で穴あきになってると次回の並び替えに不都合があるのでsort振り直す
        $search_arr['del_flg']='0';
        $all = $this->getDataAll($search_arr, 'sort asc');
        if(!empty($all)){
            $sort = 1;
            foreach($all as $val){
                $this->updateData(['id'=>$val['id']], ['sort'=>$sort]);
                $sort++;
            }
        }
    }
    // -------------------------------------------------------------------------
    // ソート番号振り直し
    // -------------------------------------------------------------------------
    /**
     * @param array $search_arr
     * del_flg がある前提で。
     */
    public function renumberSort($search_arr = [])
    {
        // del_flg で穴あきになった番号を埋めるためsort振り直す
        $search_arr['del_flg']='0';
        $all = $this->getDataAll($search_arr, 'sort asc');
        if(!empty($all)){
            $sort = 1;
            foreach($all as $val){
                $this->updateData(['id'=>$val['id']], ['sort'=>$sort]);
                $sort++;
            }
        }
    }
    // -------------------------------------------------------------------------
    // 重複しているかどうか
    // -------------------------------------------------------------------------
    public function isDuplicate($data, $colname, $table_name = '', $pkey_name = 'id')
    {
        if (empty($table_name)) {
            $table_name = $this->table_name;
        }
        $sql = "Select * From {$table_name} ";

        $search_arr = [];
        if ($this->hasField($table_name, 'del_flg')) {
            $search_arr['del_flg'] = 0;
        }
        // 既存データだったら同じID除外
        if (!empty($data[$pkey_name])) {
            $search_arr[$pkey_name] = [$data[$pkey_name], '<>'];
        }
        // チェックしたいコード
        $search_arr[$colname] = $data[$colname];

        list($search_keys, $prm) = $this->getConditions($search_arr);        // 条件
        $sql .= $search_keys;

        sql_log($sql, $prm, 'dao isDuplicate');
        return (!empty($this->getOne($sql, $prm))) ? true : false;
    }
    // -------------------------------------------------------------------------
    // コード発番
    // fill_hole_flg = true 穴埋めして採番する（欠番を作らない）
    // -------------------------------------------------------------------------
    public function getNewCode($len = 3, $pkey_name = '', $search_arr = [], $table_name = '', $fill_hole_flg = false)
    {
        if (empty($table_name)) {
            $table_name = $this->table_name;
        }
        if (empty($pkey_name)) {
            $pkey_name = $this->pkey_name;
        }
        $sql = "SELECT {$pkey_name} FROM {$table_name} ";
        list($search_keys, $prm) = $this->getConditions($search_arr);        // 条件
        $cnt = $this->getOne($sql . $search_keys, $prm);

        if (empty($cnt)) {
            return sprintf("%0{$len}d", 1);
        } else {
            if ($fill_hole_flg) {
                $sql = "SELECT min(new_code) FROM (
					SELECT lpad(($pkey_name} + 1),{$len}, '0') as new_code 
					FROM {$table_name} 
					WHERE lpad(({$pkey_name} + 1),{$len}, '0') NOT IN (SELECT {$pkey_name} FROM {$table_name} {$search_keys}) 
				)t";
            } else {
                $sql
                    = "select lpad(({$pkey_name} + 1),{$len}, '0') as new_code from {$table_name} {$search_keys} order by new_code desc limit 1";
            }

            sql_log($sql, $prm, 'dao getNewCode');
            return $this->getOne($sql, $prm);
        }
    }


    /**
     * 任意のテーブルからデータを取得して連想配列にして返す
     *
     * @param        $table_name
     * @param array  $cols
     * @param array  $search_arr
     * @param string $orderby
     *
     * @return array
     */
    public function getMaster($table_name, $cols = ['id', 'name'], $search_arr = [], $orderby = '')
    {
        $sql = "select " . implode(", ", $cols) . " from {$table_name}";
        if ($this->hasField($table_name, 'del_flg')) {
            $search_arr['del_flg'] = 0;
        }
        list($search_keys, $prm) = $this->getConditions($search_arr);
        $sql .= $search_keys;
        if (!empty($orderby)) {
            $sql .= " order by " . $orderby;
        } else {
            if ($this->hasField($table_name, 'sort')) {
                $sql .= " order by sort";
            }
        }
        $all = $this->getAllAssoc($sql, $prm);
        if (empty($all)) {
            return [];
        } else {
            return array_column($all, $cols[1], $cols[0]);
        }
    }

    // 削除分も含める
    public function getMasterAll($table_name, $cols = ['id', 'name'], $search_arr = [], $orderby = '')
    {
        $sql = "select " . implode(", ", $cols) . " from {$table_name}";
        list($search_keys, $prm) = $this->getConditions($search_arr);
        $sql .= $search_keys;
        if (!empty($orderby)) {
            $sql .= " order by " . $orderby;
        } else {
            if ($this->hasField($table_name, 'sort')) {
                $sql .= " order by sort";
            }
        }
        $all = $this->getAllAssoc($sql, $prm);
        if (empty($all)) {
            return [];
        } else {
            return array_column($all, $cols[1], $cols[0]);
        }
    }

    /**
     * 任意のテーブルから１行取得する
     *
     * @param       $table_name
     * @param array $search_arr
     *
     * @return bool
     */
    public function getDataRow($search_arr = [], $table_name = '', $check_del_flg=true)
    {
        if (empty($table_name)) {
            $table_name = $this->table_name;
        }
        $sql = "select * from {$table_name} ";
        if ($check_del_flg && $this->hasField($table_name, 'DEL_FLG')) {
            $search_arr['DEL_FLG'] = 0;
        }
        list($search_keys, $prm) = $this->getConditions($search_arr);
        //sql_log($sql.$search_keys, $prm);
        return $this->getRowAssoc($sql . $search_keys, $prm);
    }

    /**
     * 任意のテーブルから複数行取得する
     *
     * @param       $table_name
     * @param array $search_arr
     *
     * @return bool
     */
    public function getDataAll($search_arr = [], $orderby = '', $table_name = '')
    {
        if (empty($table_name)) {
            $table_name = $this->table_name;
        }

        $sql = "select * from {$table_name}";
        if ($this->hasField($table_name, 'del_flg')) {
            $search_arr['del_flg'] = 0;
        }
        list($search_keys, $prm) = $this->getConditions($search_arr);
        $sql .= $search_keys;
        if (!empty($orderby)) {
            $sql .= " order by " . $orderby;
        }
        echo getsql($sql, $prm);
        // sql_log($sql, $prm, 'dao getDataAll');
        return $this->getAllAssoc($sql, $prm);
    }

    public function getDataOne($search_arr = [], $colname, $table_name = '', $check_del_flg=true)
    {
        if (empty($table_name)) {
            $table_name = $this->table_name;
        }
        $sql = "select {$colname} from {$table_name} ";
        if ($check_del_flg && $this->hasField($table_name, 'del_flg')) {
            $search_arr['del_flg'] = 0;
        }
        list($search_keys, $prm) = $this->getConditions($search_arr);
        // sql_log($sql . $search_keys, $prm, "dao getDataOne");
        return $this->getOne($sql . $search_keys, $prm);
    }

    public function updateData($search_arr = [], $data_arr = [], $table_name = '')
    {
        if (empty($table_name)) {
            $table_name = $this->table_name;
        }
        $keys = [];
        $vals = [];
        foreach ($data_arr as $k => $v) {
            $keys[] = "{$k}=?";
            $vals[] = $v;
        }
        $sql = "update {$table_name} set " . implode(",", $keys);
        list($search_keys, $prm) = $this->getConditions($search_arr);
        //logsave("test updateData", getSql($sql . $search_keys, array_merge($vals, $prm)));
        return $this->query($sql . $search_keys, array_merge($vals, $prm));
    }

    /**
     * 検索条件や並びを生成して返す
     *
     * @param array $search_arr
     *   比較演算子ありの場合は、配列($val[0]:値, $val[1]:比較演算子)
     *
     * @return array
     */
    public function getConditions($search_arr = [])
    {
        $prm = [];
        $str = '';
        if (!empty($search_arr)) {
            $search_keys = [];
            foreach ($search_arr as $key => $val) {
                if (!is_numeric($key)) {
                    // 比較演算子ありの場合は、配列($val[0]:値, $val[1]:比較演算子)
                    if (is_array($val)) {
                        if(isset($val[2])){
                            // 比較演算子あり＆２つ or検索にする [$today, '>=', 'null', 'is']
                            // 1つ目
                            $operator1 = (isset($val[1])) ? $val[1] : '=';
                            $tmp_keys[] = "{$key} {$operator1} ?";
                            $prm[] = $val[0];
                            // 2つ目
                            $operator2 = (isset($val[3])) ? $val[3] : '=';
                            if($val[2]=='null'){
                                $tmp_keys[] = "{$key} is null";
                            } else {
                                $tmp_keys[] = "{$key} {$operator2} ?";
                                $prm[] = $val[2];
                            }
                            $search_keys[] = "(".implode(" or ", $tmp_keys).")";

                        } else {
                            // 比較演算子あり＆１つ
                            $operator = (isset($val[1])) ? $val[1] : '=';
                            $search_keys[] = "{$key} {$operator} ?";
                            $prm[] = $val[0];
                        }
                    } else {
                        $search_keys[] = "{$key} = ?";
                        $prm[] = $val;
                    }
                } else {
                    $search_keys [] = $val;
                }
            }
            $str = " where " . implode(" and ", $search_keys);
        }
        return [$str, $prm];
    }

    /**
     * 検索値をDB検索用のデータに変換して返す
     *
     * @param $search_str
     *
     * @return array
     */
    public function makeFreeWordSearch($free_word, $cols = ['name'])
    {
        $free_word = trim(str_replace(['  ', '   '], ' ', mb_convert_kana($free_word, "as")));

        $search_keys = '';
        $search_vals = [];
        if (!empty($free_word)) {
            $tmp = explode(' ', $free_word);
            $tmp2 = [];

            foreach ($tmp as $val) {
                $tmp3 = [];
                foreach ($cols as $col) {
                    $tmp3[] = "{$col} like ?";
                    $search_vals[] = '%' . mb_convert_kana($val, "a") . '%';
                }
                $tmp2[] = "(" . implode(" or ", $tmp3) . ")";
            }
            $search_keys = "(" . implode(' and ', $tmp2) . ")";
        }
        //logsave("test makeFreeWordSearch", $search_keys, $search_vals);
        return [$search_keys, $search_vals];
    }
}
