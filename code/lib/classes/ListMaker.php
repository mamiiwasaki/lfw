<?php
/**-----------------------------------------------------------------------------
 *  Lightweight framework Rev 1.1
 *-----------------------------------------------------------------------------
 *  2006-07-24 : initial version ushizawa
 *  2014-07-01 : rebuild
 *
 *  util_list class
 *
 * 一覧を生成するクラス。
 * 検索地のセット、ページングを管理する
 *
 * setSearchData    データセット（初期値, container, post）
 * $list_class->setSearch('public_date', date('Y-m-d'), '<=');
 * $list_class->setFreeWord("(title collate utf8_unicode_ci like ? or contents collate utf8_unicode_ci like ?)");
 * setSearchString($key, $val)
 * setSearchFromTo($key)
 * createPaging
 * createPaging2
 */

/*----------------------------------------------------------------------------*/

class ListMaker
{
    /** @var string */
    public $_table = '';
    /** @var string */
    public $session_name = '';     // 絞り込み内容を保持するセッション名
    /** @var array */
    public $initials = [];         // 初期値
    /** @var array */
    public $data = [];             // 検索値
    /** @var array */
    public $data_not_use = [];     // 検索条件にしたくない項目
    /** @var string */
    public $orderby = 'ID asc';
    /** @var int */
    public $limit = 50;
    /** @var int */
    public $current_page = 1;
    /** @var int */
    public $total_rec = 0;
    /** @var string */
    public $sql = '';
    /** @var array */
    public $search_key = [];
    /** @var array */
    public $search_val = [];
    /** @var string */
    public $where_str = '';

    /**
     * 検索値をリセットして初期値をセット
     */
    function resetSearchData()
    {
        global $data;
        // reset
        $this->data = [];
        // 初期値の設定がある場合
        foreach ($this->initials as $name => $val) {
            if (is_array($val)) {
                foreach ($val as $v) {
                    $this->data[$name][$v] = $v;
                }
            } else {
                $this->data[$name] = $val;
            }
        }
        $this->current_page = 1;
        $data->setAttribute($this->session_name, $this->data);
    }

    /**
     * 検索値のセット
     * 初期値 or datacontainer or post
     */
    public function setSearchData()
    {
        global $data;
        $getdata = filter_input_array(INPUT_GET);
        $postdata = filter_input_array(INPUT_POST);

        if ($data->hasAttribute($this->session_name)) {
            $this->data = $data->getAttribute($this->session_name);
        }

        if (isset($getdata['reset']) || (!empty($postdata['reset']) && $postdata['reset'] == '1')
            || (empty($postdata)
                && !$data->hasAttribute($this->session_name))
        ) {
            //-----------------------------------------
            // リセット時、POSTもデータコンテナもない時初期値セット
            //-----------------------------------------
            // reset
            $this->resetSearchData();

        } elseif (isset($getdata['setpost']) || isset($postdata['setpost'])) {
            //-----------------------------------------
            // POSTされたデータをセット
            //-----------------------------------------
            // list.js使わない時はsetpostタグをしこむ!!
            $this->data = $postdata;
        }
        //-----------------------------------------
        // get page no
        //-----------------------------------------
        if (!empty($getdata['page']) && $getdata['page'] !== 'undefined') {
            $this->current_page = (int)$getdata['page'];
            $this->data['current_page'] = $this->current_page;

            //-----------------------------------------
            // set current page
            //-----------------------------------------
        } elseif (!empty($this->data['current_page'])) {
            $this->current_page = $this->data['current_page'];
        }
        // set
        $data->setAttribute($this->session_name, $this->data);
    }

    /**
     * データをセットする
     * @param string $key
     * @param mixed $val
     */
    public function setData($key, $val)
    {
        global $data;
        $this->data = $data->getAttribute($this->session_name);
        $this->data[$key] = $val;
        $data->setAttribute($this->session_name, $this->data);
    }

    /**
     * 検索結果一覧
     * @param bool $check_del_flg del_flg=0 を入れるか
     * @param bool $check_del_flg
     * @return mixed
     */
    public function search($check_del_flg = true)
    {
        // 対象テーブルのカラムを取得
        $fields = UtilDao::getFields($this->_table);

        // 検索値セット
        // テーブルに含まれるカラムと同名だったら検索値セット
        if (!empty($this->data)) {
            foreach ($this->data as $key => $val) {
                // 対象テーブルにないカラムだったらスルー
                if (!key_exists($key, $fields)) {
                    continue;
                }
                // 使用しないデータだったらスルー
                if (!empty($this->data_not_use) && array_key_exists($key, $this->data_not_use)) {
                    continue;
                }
                $this->setSearch($key, $val);
            }
        }
        // 削除フラグ見るか？
        if ($check_del_flg && array_key_exists('del_flg', $fields)) {
            $this->setSearch('del_flg', '0');
        }
        // 条件式取得
        if (!empty($this->search_key)) {
            $this->where_str = 'where ' . implode(" AND ", $this->search_key);
        }
        // 並び
        $orderby = (!empty($this->orderby)) ? "order by $this->orderby" : '';
        // SQL
        if (!empty($this->sql)) {
            // sql指定
            $sql = $this->sql . " {$this->where_str} {$orderby}";
        } else {
            $sql = "SELECT * FROM {$this->_table} {$this->where_str} {$orderby}";
        }

        // limit 指定
        if (!empty($this->limit)) {
            // offset
            $offset = ($this->current_page - 1) * $this->limit;
            // ページングのために結果件数を取得
            $res = ORM::for_table($this->_table)->raw_query("select count(*)cnt from ($sql)tt", $this->search_val)->find_one();
            $this->total_rec = $res->cnt;
            // sql
            $sql .= " limit $this->limit offset " . (int)$offset;
        }
        sql_log($sql, $this->search_val, 'dao ListMaker search');
        return ORM::for_table($this->_table)->raw_query($sql, $this->search_val)->find_many();
    }

    /**
     * フリーワード検索
     * @param string $select_str
     * @param string $colname
     * @param string $type
     */
    public function setFreeWord($select_str, $colname = 'free_word', $type = 'and')
    {
        if (!empty($this->data[$colname])) {
            $val = $this->data[$colname];
        } else {
            return;
        }

        /////////////////////////////////////////////////////
        // フリーワード検索
        $free_word = mb_convert_kana(trim($val), "ns");
        $free_word = trim($free_word);
        $res = explode(" ", $free_word);    // 空白区切り

        $tmp = [];
        $p_cnt = substr_count($select_str, "?");

        foreach ($res as $k) {
            $tmp[] = $select_str;
            for ($i = 0; $i < $p_cnt; $i++) {
                $this->search_val[] = "%" . $k . "%";
            }
        }
        $this->search_key['free_word'] = "(" . implode(" $type ", $tmp) . ")";
    }

    /**
     * Where文を指定
     * @param string $where_str
     * @param array $prm
     */
    public function setWhereRaw($where_str, $prm)
    {
        $this->search_key[] = $where_str;

        foreach ($prm as $val) {
            $this->search_val[] = $val;
        }
    }

    /**
     * 検索値をセット
     * @param string $key
     * @param string $val
     * @param string $inc
     */
    public function setSearch($key, $val = '', $inc = '=')
    {
        // val未指定だったら、$this->data から取得
        if (!is_array($val) && strcmp($val, '') === 0) {    // todo
            if (strpos($key, '.') !== false) {
                // [.]付きだったら、table.col_name
                $tmpa = explode('.', $key);
                $val = $this->data[$tmpa[1]] ?? '';
            } else {
                $val = $this->data[$key] ?? '';
            }
        }
        //---------------------------------
        // チェックボックス（複数選択 or ）
        if (is_array($val)) {
            $tmp_k = [];
            foreach ($val as $k => $v) {
                $tmp_k[] = "$key=?";
                $this->search_val[] = $v;
            }
            $this->search_key[] = "(" . implode(" or ", $tmp_k) . ")";
            //---------------------------------
            // テキスト、セレクトボックス、ラジオ
        } elseif (!empty($val) || strcmp($val, '') !== 0) {
            // search_key
            $this->search_key[] = "{$key} {$inc} ?";
            // search_val
            if ($inc === 'like') {
                $this->search_val[] = '%' . $val . '%';
            } else {
                $this->search_val[] = $val;
            }
        }
    }

    /**
     * SQL文指定したい場合
     * @param string $key
     * @param string|int $val
     */
    public function setSearchString($key, $val)
    {
        $this->search_key[] = $key;
        if (is_array($val)) {
            foreach ($val as $v) {
                $this->search_val[] = $v;
            }
        } else {
            $this->search_val[] = $val;
        }
    }

    /**
     * from ~ to
     * @param string $key
     */
    public function setSearchFromTo($key)
    {
        if (strpos($key, '.') !== false) {
            $tmpa = explode('.', $key);
            $key_from = $tmpa[1] . "_from";
            $key_to = $tmpa[1] . "_to";
        } else {
            $key_from = $key . "_from";
            $key_to = $key . "_to";
        }
        if ((!empty($this->data[$key_from]))) {
            if (is_numeric($this->data[$key_from])) {
                if (strlen($this->data[$key_from]) == 8) {
                    $this->data[$key_from] = date('Y/m/d', strtotime($this->data[$key_from]));
                }
                if (strlen($this->data[$key_from]) == 6) {
                    $this->data[$key_from] = date('Y/m/01', strtotime($this->data[$key_from] . '01'));
                }
            }
            $this->search_key[] = $key . ">=?";
            $this->search_val[] = $this->data[$key_from] . ' 00:00:00';
        }
        if ((!empty($this->data[$key_to]))) {
            if (is_numeric($this->data[$key_to])) {
                if (strlen($this->data[$key_to]) == 8) {
                    $this->data[$key_to] = date('Y/m/d', strtotime($this->data[$key_to]));
                }
                if (strlen($this->data[$key_to]) == 6) {
                    $this->data[$key_to] = date('Y/m/t', strtotime($this->data[$key_to] . '01'));
                }
            }
            $this->search_key[] = $key . "<=?";
            $this->search_val[] = $this->data[$key_to] . ' 23:59:59';
        }
    }

    /**
     * @return string
     */
    public function createPaging()
    {
        if (empty($this->total_rec)) {
            return '';
        }
        $pager = '';

        $total_page = ceil($this->total_rec / $this->limit); //総ページ数
        $show_nav = 5;   //表示するナビゲーションの数

        //全てのページ数が表示するページ数より小さい場合、総ページを表示する数にする
        if ($total_page < $show_nav) {
            $show_nav = $total_page;
        }

        //総ページの半分
        $show_navh = floor($show_nav / 2);
        //現在のページをナビゲーションの中心にする
        $loop_start = $this->current_page - $show_navh;
        $loop_end = $this->current_page + $show_navh;
        //現在のページが両端だったら端にくるようにする
        if ($loop_start <= 0) {
            $loop_start = 1;
            $loop_end = $show_nav;
        }
        if ($loop_end > $total_page) {
            $loop_start = $total_page - $show_nav + 1;
            $loop_end = $total_page;
        }

        //2ページ移行だったら「一番前へ」を表示
        if ($this->current_page >= 2) {
            $pager .= "<li data-page='1'><i><<</i></li>\n";
        } else {
            $pager .= "<li class='inactive'><i><<</i></li>\n";
        }
        //最初のページ以外だったら「前へ」を表示
        if ($this->current_page > 1) {
            $pager .= "<li data-page='" . ($this->current_page - 1) . "'><i><</i></li>\n";
        } else {
            $pager .= "<li class='inactive'><i><</i></li>\n";
        }
        // no
        for ($i = $loop_start; $i <= $loop_end; $i++) {
            if ($i > 0 && $total_page >= $i) {
                if ($i == $this->current_page) {
                    // 現在のページ
                    $pager .= "<li class='active'><strong>{$i}</strong></li>\n";
                } else {
                    $pager .= "<li data-page='{$i}'><i>{$i}</i></li>\n";
                }
            }
        }
        //最後のページ以外だったら「次へ」を表示
        if ($this->current_page < $total_page) {
            $pager .= ' <li data-page="' . ($this->current_page + 1) . '"><i>></i></li>' . "\n";
        } else {
            $pager .= "<li class='inactive'><i>></i></li>\n";
        }
        //最後から２ページ前だったら「一番最後へ」を表示
        if ($this->current_page <= $total_page - 1) {
            $pager .= "<li data-page='{$total_page}'><i>>></i></li>\n";
        } else {
            $pager .= "<li class='inactive'><i>>></i></li>\n";
        }
        return sprintf("<span class='count'>%s 件</span>\n %s", number_format($this->total_rec), $pager);
    }

    /**
     * ユーザサイト用のページング
     * @return string
     */
    public function createPagingUser()
    {
        if (empty($this->total_rec)) {
            return '';
        }
        //総ページ数
        $total_page = ceil($this->total_rec / $this->limit);
        $min = '';
        $max = '';
        $next = '';
        $prev = '';
        if ($total_page > 1) {
            $min = 1;
            $max = $total_page;
            $prev = ($this->current_page) <= 1 ? 1 : $this->current_page - 1;
            $next = ($this->current_page) >= $total_page ? $total_page : $this->current_page + 1;
        }
        $data_pos = (isset($pos)) ? "data-pos={$pos}" : '';
        $pager = "<li class='pager--latest' data-page='{$min}' {$data_pos}></li>
                <li class='pager--prev' data-page='{$prev}' {$data_pos}></li>
                <li class='pager--current'><i>{$this->current_page}</i>/<span>{$total_page}</span></li>
                <li class='pager--next' data-page='{$next}' {$data_pos}></li>
                <li class='pager--last' data-page='{$max}' {$data_pos}></li>
            ";
        return $pager;
    }
}
