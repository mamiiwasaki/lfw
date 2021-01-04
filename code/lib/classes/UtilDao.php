<?php

class UtilDao extends ORM
{
    /**
     * 一行取得
     * @param string $table
     * @param int $id
     * @param string $idname
     * @return mixed
     */
    public static function get($table, $id, $idname = 'id')
    {
        return ORM::for_table($table)->where($idname, $id)->find_one();
    }

    /**
     * 名称取得
     * @param string $table
     * @param int $id
     * @param string $idname
     * @param string $colname
     * @return |null
     */
    public static function getName($table, $id, $idname = 'id', $colname = 'name')
    {
        $res = ORM::for_table($table)->select($colname)->where($idname, $id)->find_one();
        return (!empty($res)) ? $res->$colname : null;
    }

    /**
     * マスタを取得して配列を生成する
     * @param string $table
     * @param array $where_arr
     * @param string[] $columns
     * @return array
     */
    public static function getMaster($table, $where_arr = [], $columns = ['id', 'name'])
    {
        if (self::hasField($table, 'del_flg')) {
            $where_arr['del_flg'] = '0';
        }
        if (empty($where_arr)) {
            $result = ORM::for_table($table)->order_by_asc('sort')->find_array();
        } else {
            $result = ORM::for_table($table)->where($where_arr)->order_by_asc('sort')->find_array();
        }
        $arr = [];
        if (!empty($result)) {
            foreach ($result as $val) {
                $arr[$val[$columns[0]]] = $val[$columns[1]];
            }
        }
        return $arr;
    }

    /**
     * 指定のテーブルのカラムリストを配列で返す
     * @param string $table
     * @return array
     */
    public static function getFields($table)
    {
        $result = ORM::for_table($table)->raw_query("desc $table")->find_array();
        return array_fill_keys(array_column($result, 'Field'), null);
    }

    /**
     * カラムがあるかどうか
     * @param string $table
     * @param string $colname
     * @return bool
     */
    public static function hasField($table, $colname)
    {
        $res = ORM::for_table($table)->raw_query("DESCRIBE {$table} {$colname}")->find_one();
        return (!empty($res)) ? true : false;
    }

    /**
     * 指定のカラムの値を取得
     * @param string $table
     * @param string $colname
     * @param array $where_arr
     * @return string
     */
    public static function getOne($table, $colname, $where_arr = [])
    {
        $res = ORM::for_table($table)->select($colname)->where($where_arr)->find_array();
        return (!empty($res)) ? $res[0][$colname] : '';
    }

    /**
     * レコードを一行取得
     * @param string $table
     * @param array $where_arr
     * @return mixed|null
     */
    public static function getRow($table, $where_arr = [])
    {
        $res = ORM::for_table($table)->where($where_arr)->find_array();
        return (!empty($res[0])) ? $res[0] : null;
    }

    /**
     * レコードを複数行取得
     * @param string $table
     * @param array $where_arr
     * @param string $orderby
     * @param string $limit
     * @return mixed
     */
    public static function getAll($table, $where_arr = [], $orderby = '', $limit = '')
    {
        $orm = ORM::for_table($table)->where($where_arr);
        if (self::hasField($table, 'del_flg')) {
            $orm->where('del_flg', '0');
        }
        if (!empty($orderby)) {
            $orm->order_by_expr($orderby);
        }
        if (!empty($limit)) {
            $orm->limit($limit);
        }
        return $orm->find_array();
    }

    /**
     * @param string $table
     * @return mixed
     */
    public static function _get_id_column_name2($table)
    {
        // connection_name はとりあえず default で
        if (isset(self::$_config['default']['id_column_overrides'][$table])) {
            return self::$_config['default']['id_column_overrides'][$table];
        }
        return self::$_config['default']['id_column'];
    }

    /**
     * データ保存 insert or update
     * @param string $table
     * @param array $data
     * @param string $id_column
     * @return int|bool
     */
    public static function save($table, $data, $id_column = '')
    {
        if (empty($data)) {
            return false;
        }
        if (empty($id_column)) {
            $id_column = self::_get_id_column_name2($table);
        }
        // 更新者、更新日時情報をセットする、データきれいに
        $data = self::_setData($table, $data);
        // 登録データあるか
        $orm = null;
        if (isset($data[$id_column])) {
            // 削除フラグは参照しない。
            $orm = ORM::for_table($table)->where($id_column, $data[$id_column]);
            $res = $orm->find_one();
        }
        // insert or update
        if (empty($res)) {
            //------------------------------------
            // insert
            $orm = ORM::for_table($table)->create();
            foreach ($data as $key => $val) {
                $orm->set($key, $val);
            }
        } else {
            //------------------------------------
            // update
            foreach ($data as $key => $val) {
                $orm->set($key, $val);
            }
        }
        // save
        $orm->save();
        self::saveLog(__METHOD__);
        return $orm->id;
    }

    /**
     * insert
     * @param string $table
     * @param array $data
     */
    public static function insert($table, $data)
    {
        // 更新者、更新日時情報をセットする、データきれいに
        $data = self::_setData($table, $data);
        // save
        return ORM::for_table($table)->create()
            ->set($data)
            ->save();
        self::saveLog(__METHOD__);
    }

    /**
     * update
     * @param string $table
     * @param array $where_arr
     * @param array $data
     */
    public static function update($table, $where_arr = [], $data = [])
    {
        // 更新者、更新日時情報をセットする、データきれいに
        $data = self::_setData($table, $data);
        // save
        return ORM::for_table($table)
            ->where($where_arr)
            ->find_result_set()
            ->set($data)
            ->save();
        self::saveLog(__METHOD__);
    }

    /**
     * 論理削除
     * @param string $table
     * @param int $id
     */
    public static function delete($table, $id)
    {
        global $user;
        $data_arr['update_user_id'] = $user->emp_no ?? '';
        $data_arr['updated_at'] = date('Y/m/d H:i:s');
        $data_arr['del_flg'] = 1;
        ORM::for_table($table)->where('id', $id)->find_one()->set($data_arr)->save();
        return self::saveLog(__METHOD__);
    }

    /**
     * 更新者、更新日時情報をセットする、データきれいに
     * @param string $table
     * @param array $data
     * @return array
     */
    public static function _setData($table, $data)
    {
        // テーブルのカラムを取得
        $fields = self::getFields($table);
        // 登録情報セット
        global $user;
        $now = date('Y-m-d H:i:s');
        $data['created_at'] = $now;
        $data['updated_at'] = $now;
        $data['create_user_id'] = $user->emp_no ?? '';
        $data['update_user_id'] = $user->emp_no ?? '';
        $data_new = [];
        foreach ($data as $key => $val) {
            // テーブルにないカラムだったらスルー
            if (!array_key_exists($key, $fields)) {
                continue;
            }
            if (!is_array($val)) {
                $val = trim($val);
                // replace
                $pattern_arr = ['<script', '/script>', '~'];
                $convert_arr = ['＜script', '/script＞', '〜'];
                $val = str_replace($pattern_arr, $convert_arr, $val);
                // 空文字をnullに変える
                if (strcmp($val, '') == 0) {
                    $val = null;
                }
                $data_new[$key] = $val;
            }
        }
        return $data_new;
    }

    /**
     * ログを出力する
     * @param string $tag
     */
    public static function saveLog($tag = '')
    {
        if (DEBUG_QUERY) {
            logsave("dao " . $tag, ORM::get_query_log());
        }
    }
    
    /**
     * 新しいソート取得
     * @param string $table
     * @param array $where_arr
     * @return string
     */
    public static function getNewSort($table, $where_arr = [])
    {
        $orm = ORM::for_table($table)->select_expr('max(sort)+1', 'max');
        // del_flg
        if (self::hasField($table, 'del_flg')) {
            $orm->where('del_flg', '0');
        }
        // where
        if (!empty($where_arr)) {
            $orm->where($where_arr);
        }
        $res = $orm->find_one();
        return $res->max ?? '1';
    }
    
    /**
     * ソート更新
     * del_flg がある前提で。
     * del_flg で穴あきになってると次回の並び替えに不都合があるのでsort振り直す
     * @param string $table
     * @param int $id
     * @param int $sort
     * @param int $sort_to
     */
    public static function updateSort($table, $id, $sort, $sort_to)
    {
        if ($sort > $sort_to) {
            //---------------------------------------
            // 小さくする (自分がなりたいsortより後ろのソートたちを+1)
            //---------------------------------------
            ORM::raw_execute("update {$table} set sort=(sort+1) where sort >=?", [$sort_to]);
        } else {
            //---------------------------------------
            // 大きくする (自分のsortからなりたいsortまで -1)
            //---------------------------------------
            ORM::raw_execute("update {$table} set sort=(sort-1) where sort between ? and ?", [$sort, $sort_to]);
        }
        // 自分をなりたいsortに
        ORM::raw_execute("update {$table} set sort=? where id=?", [$sort_to, $id]);

        // del_flg で穴あきになってると次回の並び替えに不都合があるのでsort振り直す
        $all = ORM::for_table($table)->select_many('id', 'sort')->where('del_flg', '0')->order_by_asc('sort')->find_array();
        if (!empty($all)) {
            $sort = 1;
            foreach ($all as $val) {
                self::updateData($table, ['id' => $val['id']], ['sort' => $sort]);
                $sort++;
            }
        }
    }
    
    /**
     * 重複しているかどうかを返す
     * @param string $table
     * @param array $data
     * @param string $colname
     * @param string $id_column
     * @return bool
     */
    public static function isDuplicate($table, $data, $colname, $id_column = 'id')
    {
        // select
        $orm = ORM::for_table($table)->select($colname);
        // チェックしたいキー
        $orm->where($colname, $data[$colname]);
        // del_flg
        if (self::hasField($table, 'del_flg')) {
            $orm->where('del_flg', '0');
        }
        // 既存データだったら同じID除外
        if (!empty($data[$id_column])) {
            $orm->where_not_equal($id_column, $data[$id_column]);
        }
        return (!empty($orm->find_one())) ? true : false;
    }
    
    /**
     * XXXとか、ABC-XXX などのパターンのコードの次番号採番 (.*?)
     * @param string $table
     * @param string $_id_column
     * @param string $format
     * @return string
     */
    public static function getNewCode($table, $_id_column, $format = 'f%02d')
    {
        // formatを分解
        preg_match("/([0-9a-zA-Z-_]+)%([0-9]+)d/", $format, $matches);
        // 次の番号を取得
        $orm = ORM::for_table($table)->select_expr("max(replace({$_id_column}, '{$matches[1]}', ''))+1", 'next_code');
        // del_Flg
        if (self::hasField($table, "del_flg")) {
            $orm->where('del_flg', '0');
        }
        $next_code = $orm->find_one() ?? 1;
        // formatを適用して返す
        return sprintf($matches[1] . '%' . $matches[2] . 'd', $next_code->next_code);
    }
}
