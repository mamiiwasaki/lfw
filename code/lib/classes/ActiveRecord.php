<?php

class ActiveRecord extends Model
{
    public $table_name = '';
    public $primary_key = '';

    function __construct()
    {
        if (DEBUG) {
            ORM::configure('logging', true);
        }
    }

    public function save()
    {
        // 現在時刻を取得
        $now = date('Y-m-d H:i:s');
        // 新規作成か確認
        if ($this->is_new()) {
            // 新規時のみ作成日をセット
            $this->created_at = $now;
        }
        // 更新日をセット
        $this->updated_at = $now;
        // 更新処理を実施
        return parent::save();
    }

    /*----------------------------------------------
     * 論理削除
     */
    public function deleteData($id, $table_name)
    {
        global $user;

        $data_arr['update_user_id'] = $user->id;
        $data_arr['updated_at'] = date('Y/m/d H:i:s');
        $data_arr['del_flg'] = 1;

        ORM::for_table($table_name)->where_equal('id', $id)->find_one()
            ->set($data_arr)->save();
        return;
    }

    /*----------------------------------------------
     * 更新
     */
    public function updateData($where_arr = [], $set_arr = [], $table_name = '')
    {
        global $user;
        $set_arr['update_user_id'] = $user->id;
        $set_arr['updated_at'] = date('Y/m/d H:i:s');
        if (empty($table_name)) {
            $table_name = $this->table_name;
        }
        Model::factory($table_name)
            ->where($where_arr)
            ->find_result_set()
            ->set($set_arr)
            ->save();
        return;
    }
    /**----------------------------------------------
     * 名称を取得
     */
    static function getName($table_name, $where_arr = [], $colname = 'name')
    {
        return Model::factory($table_name)->select($colname)->where($where_arr)->findArray()[0][$colname];
    }
    /**----------------------------------------------
     * マスタを配列で取得
     */
    static function getMaster($table_name, $where_arr = [], $columns = ['id', 'name'])
    {
        if (self::hasField($table_name, 'del_flg')) {
            $where_arr['del_flg'] = '0';
        }
        if (empty($where_arr)) {
            $result = Model::factory($table_name)->order_by_asc('sort')->findArray();
        } else {
            $result = Model::factory($table_name)->where($where_arr)->order_by_asc('sort')->findArray();
        }
        $arr = [];
        if (!empty($result)) {
            foreach ($result as $val) {
                $arr[$val[$columns[0]]] = $val[$columns[1]];
            }
        }
        return $arr;
    }

    /**----------------------------------------------
     * 指定のテーブルのカラムリストを配列で返す
     */
    static function getFiels($table_name)
    {
        $result = Model::rawQuery("show columns from {$table_name}")->findMany();
        if (!empty($result)) {
            foreach ($result as $val) {
                $fields[] = $val->Field;
            }
        }
        return $fields;
    }

    // カラムがあるかどうか
    static function hasField($table_name, $colname)
    {
        return (!empty(Model::rawQuery("DESCRIBE {$table_name} {$colname}")->findOne())) ? true : false;
    }

    /*----------------------------------------------
     * 指定のカラムを取得
     */
    public static function getDataOne($where_arr = [], $col_name, $table_name)
    {
        $res = Model::factory($table_name)->select($col_name)->where($where_arr)->findArray();
        return (!empty($res)) ? $res[0][$col_name] : '';
    }

    /*----------------------------------------------
     * レコードを一行取得
     */
    public static function getDataRow($where_arr = [], $table_name = '')
    {
        $res = Model::factory($table_name)->where($where_arr)->findArray();
        return (!empty($res[0])) ? $res[0] : null;
    }

    /*----------------------------------------------
     * レコードを複数行取得
     */
    public static function getDataAll($where_arr = [], $orderby = 'id asc', $table_name)
    {
        $where_arr['del_flg'] = 0;
        return Model::factory($table_name)->where($where_arr)->findArray();
    }

    /*----------------------------------------------
     * insert or update
     */
    public function saveData($data, $table_name = '', $primary_key = '')
    {
        if (empty($data)) {
            return;
        }
        if (empty($table_name)) {
            $table_name = $this->table_name;
        }
        if (empty($primary_key)) {
            $primary_key = $this->primary_key;
        }
        global $user;

        // 空文字をnullに変える
        if(!empty($data)){
            foreach($data as $key=>$val){
                if(!is_array($val)){
                    if(strcmp($val, '')==0){
                        $data[$key]=null;
                    }
                }
            }
        }
        // fields
        $fields = $this->getFiels($table_name);
        $now = date('Y-m-d H:i:s');
        // 既存データがあるかチェック
        $check = ORM::for_table($table_name)->where($primary_key, $data[$primary_key])->find_one();
        if (empty($check)) {
            //------------------------------------
            // insert
            $orm = ORM::for_table($table_name)->create();
            foreach ($data as $key => $val) {
                if (!in_array($key, $fields)) {
                    continue;
                }
                $orm->set($key, $val);
            }
            $orm->created_at = $now;
            $orm->create_user_id = $user->id;
            $orm->updated_at = $now;
            $orm->update_user_id = $user->id;
            $orm->id = null;
            unset($orm->id);
            $orm->save();
           // logsave("test saveData insert", ORM::get_last_query());
            // last_insert_id (なぜか $orm->id() ダメ)
            $res = ORM::for_table($table_name)->order_by_desc('id')->find_one();
            $id = $res->id;

        } else {
            //------------------------------------
            // update
            $orm = $check;
            foreach ($data as $key => $val) {
                if (!in_array($key, $fields)) {
                    continue;
                }
                $orm->set($key, $val);
            }
            $orm->updated_at = $now;
            $orm->update_user_id = $user->id;
            $orm->save();
            $id = $orm->$primary_key;
          //  logsave("test saveData update", ORM::get_last_query());
        }
        return $id;
    }

    // ログを出力する
    public static function saveLog()
    {
        logsave("test orm log", ORM::get_query_log());
//        ORM::configure('logger', function ($log_string, $query_time) {
//            logsave("test logger", $log_string . ' in ' . $query_time);
//        });
    }

}
