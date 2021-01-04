<?php
/*-----------------------------------------------------------------------------
 * Lightweight framework Rev 1.3
 *-----------------------------------------------------------------------------
 * 2006-07-24 : initial version
 * 2015-05-12 : rebuild by hide
 *
 * utilities
 *----------------------------------------------------------------------------*/
/**
 * 生年月日から年齢を返す
 * @param $birthday
 * @return false|float
 */
function getAge($birthday)
{
    $now = date("Ymd");
    $birthday = date('Ymd', strtotime($birthday));
    return floor(($now - $birthday) / 10000);
}

/**
 * 年齢から年代を取得
 * @param $age
 * @return string
 */
function getAges($age)
{
    if ($age < 10) {
        $str = '〜１０代';
    } elseif ($age >= 10 and $age < 20) {
        $str = '１０代';
    } elseif ($age >= 20 and $age < 30) {
        $str = '２０代';
    } elseif ($age >= 30 and $age < 40) {
        $str = '３０代';
    } elseif ($age >= 40 and $age < 50) {
        $str = '４０代';
    } elseif ($age >= 50 and $age < 60) {
        $str = '５０代';
    } elseif ($age >= 60 and $age < 70) {
        $str = '６０代';
    } elseif ($age >= 70 and $age < 80) {
        $str = '７０代';
    } elseif ($age >= 80) {
        $str = '８０代〜';
    }
    return $str;
}

/**
 * 数値の配列を生成する
 * @param $start
 * @param $end
 * @return array
 */
function getNumArr($start, $end)
{
    $arr = [];
    for ($i = $start; $i <= $end; $i++) {
        $arr[$i] = $i;
    }
    return $arr;
}

/**
 * 特定のIPのみ閲覧を許可する
 * @return bool
 */
function allow_ip()
{
    $ip = filter_input(INPUT_SERVER, 'REMOTE_ADDR');
    $allows = explode(",", ALLOW_IP_LIST);

    return in_array($ip, $allows);
}

/**
 * 年月の配列を返す
 * @param string $start
 * @param string $end
 * @param string $sort
 * @return array
 */
function getYYMMArr($start = "", $end = "", $sort = "desc")
{
    if (empty($start)) {
        $start = date("Ym01");
    } else {
        if (strlen($start) === 6) {
            $start .= '01';
        }
    }
    if (empty($end)) {
        $end = date("Ym01");
    } else {
        if (strlen($end) === 6) {
            $end .= '01';
        }
    }
    $arr = array();
    if ($sort === 'asc') {
        // 昇順
        $t = strtotime($start);
        for ($i = 0; $i < 100; $i++) {
            $t2 = mktime(0, 0, 0, date("m", $t) + $i, 1, date("Y", $t));
            $d1 = date("Ym", $t2);
            $d2 = date("Y年m月", $t2);
            $arr[$d1] = $d2;
            if (strtotime($end) <= $t2) {
                break;
            }
        }
    } else {
        // 降順
        $t = strtotime($end);
        for ($i = 0; $i < 100; $i++) {
            $t2 = mktime(0, 0, 0, date("m", $t) - $i, 1, date("Y", $t));
            $d1 = date("Ym", $t2);
            $d2 = date("Y年m月", $t2);
            $arr[$d1] = $d2;
            if (strtotime($start) >= $t2) {
                break;
            }
        }
    }
    return $arr;
}

/**
 * htmlspecialchars
 * @param $val
 * @return string
 */
function h($val)
{
    return htmlspecialchars($val);
}

/**
 * 前後のカンマを除去する
 * @param $val
 * @return bool|string
 */
function trimComma($val)
{
    if (empty($val)) {
        return $val;
    }
    if (substr($val, 0, 1) == ",") {
        $val = substr($val, 1, strlen($val));
    }
    if (substr($val, -1) == ",") {
        $val = substr($val, 0, strlen($val) - 1);
    }
    return str_replace(',,', ',', $val);
}

function mb_strpos_n($str, $needle, $n = 0, $encoding = null)
{
    if (func_num_args() < 4) {
        $encoding = mb_internal_encoding();
    }
    $offset = 0;
    $len = mb_strlen($needle, $encoding);
    while ($n-- >= 0 && ($pos = mb_strpos($str, $needle, $offset, $encoding)) !== false) {
        $offset = $pos + $len;
    }
    return $pos;
}

/**
 * yyyy/mm/dd 形式で返す
 * @param $date
 * @return string|null
 */
function getYmd($date)
{
    if (empty($date)) {
        return null;
    }
    return date('Y/m/d', strtotime($date));
}

/**
 * yyyy年mm月 形式で返す
 * @param $date
 * @return string|null
 */
function getYm($date)
{
    if (empty($date)) {
        return null;
    }
    return date('Y年m月', strtotime($date));
}

/**
 * yyyy/mm/dd H:i 形式で返す
 * @param $date
 * @return string|null
 */
function getYmdhi($date)
{
    if (empty($date)) {
        return null;
    }
    return date('Y/m/d H:i', strtotime($date));
}

/**
 * @param $time
 * @return string|null
 */
function getHis($time)
{
    if (empty($date)) {
        return null;
    }
    return date('H:i:s', strtotime($time));
}

/**
 * 拡張子を返す
 * @param $val
 * @return string|string[]
 */
function getExt($val)
{
    $ext = strtolower(substr($val, strrpos($val, '.') + 1));
    return str_replace("jpeg", "jpg", $ext);
}

/**
 * テンポラリディレクトリを返す
 * @return string
 */
function getTmpDir()
{
    return rtrim(sys_get_temp_dir(), '/') . "/";
}

/**
 * テンポラリディレクトリに格納しておいたファイルをコピーする
 * @param $dir
 * @param $tmp_name
 * @param string $file_name
 * @return string|void
 */
function copyTmpFile($dir, $tmp_name, $file_name = '')
{
    if (empty($tmp_name)) {
        return;
    }
    // 一時保管したアップロードファイルがあったらコピー
    $tmp_dir = getTmpDir();  // 一時保管ディレクトリ
    if (!file_exists(UPLOAD_DIR . $dir)) {
        mkdir(UPLOAD_DIR . $dir); // ディレクトリなかったら作成
    }
    if (!empty($file_name)) {
        // ファイル名の指定がある
        $file_name = $file_name . "." . getExt($tmp_name);
    } else {
        // テンポラリ名そのまま
        $file_name = $tmp_name;   // 乱数部分を除去
    }

    $file_path = UPLOAD_DIR . $dir . "/" . $file_name;
    if (file_exists($file_path)) {
        unlink($file_path);  // 既存があったら削除
    }
    //logsave("test copyTmpFile", $tmp_dir.$tmp_name, $file_path);
    copy($tmp_dir . $tmp_name, $file_path);  // コピー
    exec("chmod 777 " . $file_path);
    unlink($tmp_dir . $tmp_name); // テンポラリを削除しておく
    return $file_name;
}

/**
 * 文字コード変換
 * @param $val
 * @return string
 */
function s2u($val)
{
    return mb_convert_encoding($val, CHARSET, "Shift-JIS");
}

/**
 * メール送信
 * @param $to
 * @param $title
 * @param $body
 * @param $from
 * @param string $from_name
 * @return bool
 */
function mbsendmail($to, $title, $body, $from, $from_name = '')
{
    mb_language("Japanese");
    mb_internal_encoding("UTF-8");
    $option = '';
    if (!empty($from)) {
        if (!empty($from_name)) {
            $option = "From:" . mb_encode_mimeheader("{$from_name}") . $from;
        } else {
            $option = "From:" . $from;
        }
    }
    return mb_send_mail($to, $title, $body, $option);
}

/**
 * 公開中かどうか
 * @param null $start
 * @param null $end
 * @return bool
 */
function isOpened($start = null, $end = null)
{
    $today = strtotime(date('Y/m/d'));
    $start_t = strtotime($start);
    $end_t = strtotime($end);
    if ((!empty($start) && empty($end) && $start_t <= $today)
        || (!empty($end) && empty($start) && $end_t >= $today)
        || ($start_t <= $today && $end_t >= $today)
    ) {
        return true;
    }
    return false;
}

/**
 * レポート内容の整形
 * @param $val
 * @return string|string[]
 */
function convContents($val)
{
    return str_replace(["<script>", "</script>", "”", "’"], ["＜script＞", "＜/script＞", "\"", "'"], $val);
}

/**
 * file path
 * @param $dir
 * @param $file
 * @param string $nocache
 * @return string
 */
function getFilePath($dir, $file, $nocache = '&nocache=1')
{
    return "/file.html?dir={$dir}&file={$file}{$nocache}";
}

/**
 * 編集画面 データを取得
 * @param $table
 * @param null $id
 * @param string $url
 * @param array $initials
 * @return array|mixed|null
 */
function getPostData($table, $id = null, $url = '', $initials = [])
{
    global $data;
    $mode = filter_input(INPUT_GET, 'mode');
    if (!empty(filter_input_array(INPUT_POST))) {
        //------------------------
        // postされてきたデータ
        logsave('test', 'post');
        return filter_input_array(INPUT_POST);

    } elseif (!empty($mode) && $mode == 'back' && $data->hasAttribute($table)) {
        //------------------------
        // data container
        logsave('test', 'dataContainer');
        return $data->getAttribute($table);

    } elseif (empty($id)) {
        //------------------------
        // 新規
        logsave('test', 'new');
        $postdata = UtilDao::getFields($table);
        if (!empty($initials)) {
            foreach ($initials as $key => $val) {
                $postdata[$key] = $val;
            }
        }
        return $postdata;

    } else {
        //------------------------
        // 編集
        logsave('test', 'edit');
        $res = ORM::for_table($table)->where('id', $id)->find_array();
        if (empty($res)) {
            // 無効なIDだったら一覧へ飛ばす
            header('Location: ' . $url);
            exit;
        }
        return $res[0];
    }
}
