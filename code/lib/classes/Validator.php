<?php
/**-----------------------------------------------------------------------------
 *  Lightweight framework Rev 1.1
 *-----------------------------------------------------------------------------
 *  2006-07-24 : initial version ushizawa
 *  2014-07-01 : rebuild
 *  2019-11-07 : rebuild ushizawa
 *
 *  validator class
 * ーーーーーー 使い方 例 ーーーーーー
 * checkEmpty    必須                     $validator->validate("adviser_id", ["checkEmpty"], "IDを入力してください");
 * checkAccount    ID、パスワード 何桁〜何桁   $validator->validate("account", ["checkAccount", 4, 10], "アカウントは４桁〜１０桁の英数字で入力してください");
 * checkCompare   2つのデータの比較          $validator->validate('PASSWORD_CONFIRM', ['checkCompare', $validator->data['PASSWORD'], $validator->data['PASSWORD_CONFIRM'], '='], '確認用と相違します');
 * checkSeiki    正規表現指定              $validator->validate("test", ["checkSeiki", "/^[0-9-]{10,15}$/"], "テストを確認してください");
 * checkNum      数値
 * checkDate    日付
 * checkTime    時間
 * maxLength    最大文字数
 * mb_maxLength 最大文字数(半角を0.5とカウントする)
 * minLength    最小文字数                   $validator->validate("your_height", ["minLength", 0, 140], "身長は140cm以上で入力してください");
 * checkRange    範囲指定                    $validator->validate("hour", ["checkRange", 0, 24], "テスト時間は０〜２４で入力してください");
 * checkKeta    桁
 * checkTel      電話番号
 * checkTel2    電話番号
 * checkZip      郵便番号
 * checkURL      URL
 * checkEmail    Email
 * checkDomain    Domain
 * checkIP          IP
 * checkKana    カナ
 * checkHiragana  ひらがな
 * checkKanji    漢字
 * checkEisu    英数                     $validator->validate("adviser_id", ["checkEisu"], "IDを英数字入力してください");
 * checkRate    率
 * checkFileSize  ファイルサイズ
 * checkFile        ファイル名をチェックする    $validator->validate($name, ["checkFile", '/\.gif$|\.png$|\.jpg$|\.jpeg$/i'], "画像ファイルで登録してください");
 *----------------------------------------------------------------------------*/

/**
 * フォームから入力されたデータのチェックを行う.
 */
class Validator
{
    /** @var array */
    public $data = null;
    /** @var array */
    public $files = null;   // アップロードファイルがある時
    /** @var array */
    public $errors = [];

    /**
     * すべてのデータをエスケープする.
     *
     * @return bool
     */
    public function escape()
    {
        if (empty($this->data)) {
            return true;
        }
        $pattern_arr = ['<script', '/script>', '~'];
        $convert_arr = ['＜script', '/script＞', '〜'];

        foreach ($this->data as $key => $val) {
            // 配列
            if (is_array($val)) {
                foreach ($val as $k => $v) {
                    // 絵文字を[emoji]に置換する
                    $v = preg_replace('/[\xF0-\xF7][\x80-\xBF][\x80-\xBF][\x80-\xBF]/', '[*]', $v);
                    $this->data[$key][$k] = str_replace($pattern_arr, $convert_arr, $v);
                    $this->data[$key][$k] = trim($this->data[$key][$k]);
                }
                // テキスト
            } else {
                // 絵文字を[emoji]に置換する
                $val = preg_replace('/[\xF0-\xF7][\x80-\xBF][\x80-\xBF][\x80-\xBF]/', '[*]', $val);
                $this->data[$key] = str_replace($pattern_arr, $convert_arr, $val);
                $this->data[$key] = trim($this->data[$key]);
            }
        }
        return true;
    }

    public function validateArray($check_config){
        /*
         * $check_config = [
                        ['name'=>'no', 'checkEmpty'=>true, 'maxLength'=>50, 'checkNum'=>true],
                        ['name'=>'name', 'maxLength'=>50],
                        ['name'=>'belonging', 'checkEmpty'=>true, 'maxLength'=>50],
                        ['name'=>'entering', 'checkEmpty'=>true, 'maxLength'=>50],
                        ['name'=>'occupation', 'checkEmpty'=>true, 'maxLength'=>50],
                    ];
         */
        foreach($check_config as $val){
            if(!is_array($val)){
                // 配列じゃなかったら、Nullチェックのみ。
                $this->validate($val, ['checkEmpty'], '<br>入力してください');
            } else {
                if(isset($val['checkEmpty']) && $val['checkEmpty']){
                    $this->validate($val['name'], ['checkEmpty'], "<br>入力してください");
                }
                if(isset($val['checkNum']) && $val['checkNum']){
                    $this->validate($val['name'], ['checkNum'], "<br>数値で入力してください");
                }
                if(isset($val['maxLength'])){
                    $this->validate($val['name'], ['maxLength', $val['maxLength']], "<br>{$val['maxLength']}桁以内で入力してください");
                }
            }
        }
    }
    /**
     * フォーム入力チェック.
     *
     * @param string $fieldName
     * @param string $rules
     * @param string $message
     *
     * @return bool
     */
    public function validate($fieldName, $rules, $message)
    {
        // 2つ以上チェック項目がある場合
        if (!empty($this->errors[$fieldName])) {
            return true;
        }

        // rule, val
        $rule = $rules[0];
        $val = (isset($this->data[$fieldName])) ? $this->data[$fieldName] : '';

        //------------------------------------------------------
        // 入力チェック、アップロードファイル名チェック以外で空できたら、スルー
        if (!is_array($val)) {
            if ($rule != 'checkEmpty' && $rule != 'checkFile' && $rule != 'checkFileEmpty' && strcmp($val, '') === 0) {
                return true;
            }

            // 半角チェックだったら、全角＝＞半角にする処理
            if ($rule == 'checkNum' || $rule == 'checkDate' || $rule == 'checkTime' || $rule == 'checkTel'
                || $rule == 'checkTel2'
                || $rule == 'checkZip'
                || $rule == 'checkEmail'
                || $rule == 'checkDomain'
                || $rule === 'checkIP'
                || $rule == 'checkURL'
                || $rule == 'checkEisu'
                || $rule == 'checkRate'
            ) {
                $this->data[$fieldName] = mb_convert_kana($this->data[$fieldName], 'a', CHARSET);
                $this->data[$fieldName] = str_replace('−', '-', $this->data[$fieldName]);
                $val = $this->data[$fieldName];
            }
        }

        switch ($rule) {
            // 入力必須
            case 'checkEmpty':
                if (is_array($val)) {
                    // 配列
                    if (empty($val)) {
                        $this->errors[$fieldName] = $message;
                    } elseif (count($val) === 1 && empty($val[0])) {
                        // todo checkbox, radio　未選択時対応ようのhiddenがあるので
                        $this->errors[$fieldName] = $message;
                    }
                } else {
                    if (!isset($val) || strcmp($val, '') == 0) {
                        $this->errors[$fieldName] = $message;
                    }
                }
                break;

            // ID、パスワード 何桁〜何桁
            case 'checkAccount':
                if (!$this->checkAccount($val, $rules[1], $rules[2])) {
                    $this->errors[$fieldName] = $message;
                }
                break;

            // 2つのデータの比較
            case 'checkCompare':
                $togo = (!empty($rules[3])) ? $rules[3] : '=';
                if (!$this->checkCompare($rules[1], $rules[2], $togo)) {
                    $this->errors[$fieldName] = $message;
                }
                break;

            // 日付の比較
            case 'compareFromTo':
                if (!$this->compareFromTo($rules[1], $rules[2])) {
                    $this->errors[$fieldName] = $message;
                }
                break;

            // 正規表現指定
            case 'checkSeiki':
                if (!$this->checkSeiki($val, $rules[1])) {
                    $this->errors[$fieldName] = $message;
                }
                break;

            // アップロードファイル必須チェック
            case 'checkFileEmpty':
                if (empty($this->files[$fieldName]['name'])) {
                    $this->errors[$fieldName] = $message;
                }
                break;
            // アップロードファイル名のチェック
            case 'checkFile':
                $file_val = (isset($this->files[$fieldName]['name'])) ? $this->files[$fieldName]['name'] : '';
                if (empty($file_val)) {
                    return true;
                }
                if (!$this->checkSeiki($file_val, $rules[1])) {
                    $this->errors[$fieldName] = $message;
                }
                break;

            // デフォルト その他
            default:
                $rule = $rules[0];
                if (isset($rules[2])) {            // パラメータ2つ
                    if (!$this->$rule($val, $rules[1], $rules[2])) {
                        $this->errors[$fieldName] = $message;
                    }
                } elseif (isset($rules[1])) {    // パラメータ１つ
                    if (!$this->$rule($val, $rules[1])) {
                        $this->errors[$fieldName] = $message;
                    }
                } else {                        // パラメータなし
                    if (!$this->$rule($val)) {
                        $this->errors[$fieldName] = $message;
                    }
                }
                break;
        }
        return true;
    }

    /**
     * 半角数字
     * @param string|int $val
     * @return bool
     */
    public function checkNum($val)
    {
        return is_numeric($val);
    }

    /**
     * 日付チェック
     * @param date|string|int $val
     * @return bool
     */
    public function checkDate($val)
    {
        if ($val == '0000-00-00') {
            return true;
        }
        if (!preg_match("/^\d{4}(-|\/)\d{1,2}(-|\/)\d{1,2}$/", $val)) {
            return false;
        }
        $sep = substr($val, 4, 1);
        $tmp = explode($sep, $val);
        return checkdate($tmp[1], $tmp[2], $tmp[0]);
    }

    /**
     * アカウント
     * @param string|int $val
     * @param int $keta1
     * @param int $keta2
     * @return false|int
     */
    public function checkAccount($val, $keta1, $keta2)
    {
        return preg_match("/^[0-9A-Za-z\-\/\_]{{$keta1},{$keta2}}$/", $val);
    }

    /**
     * 最大桁、最小桁
     * @param string $val
     * @param int $max
     * @return bool
     */
    public function maxLength($val, $max)
    {
        return mb_strlen($val, CHARSET) <= $max;
    }

    /**
     * 半角は0.5とカウントする
     * @param string $val
     * @param int $max
     * @return bool
     */
    public function mb_maxLength($val, $max)
    {
        return mb_strwidth($val, 'UTF-8') / 2 <= $max;
    }

    /**
     * 最小値
     * @param string $val
     * @param int $min
     * @return bool
     */
    public function minLength($val, $min)
    {
        return mb_strlen($val, CHARSET) >= $min;
    }

    /**
     * 範囲指定
     * @param int $val
     * @param int $min
     * @param int $max
     * @return bool
     */
    public function checkRange($val, $min, $max)
    {
        return $val >= $min && $val <= $max;
    }
    //----------------------------------------------------------------
    // 比較
    //----------------------------------------------------------------
    /**
     * @param string|int $val1
     * @param string|int $val2
     * @param string $togo
     * @return bool
     */
    public function checkCompare($val1, $val2, $togo = '=')
    {
        if (empty($val2)) {
            return true;
        }
        //logsave("test checkCompare", $val1, $val2, $togo);
        switch ($togo) {
            case '=':
            case 'equal':
                return strcmp(trim($val1), trim($val2)) === 0 ? true : false;
                break;
            case '>':
                return trim($val1) > trim($val2);
                break;
            case '<':
                return trim($val1) < trim($val2);
                break;
            case '>=':
                return trim($val1) >= trim($val2);
                break;
            case '<=':
                return trim($val1) <= trim($val2);
                break;
        }
        return true;
    }

    /**
     * 桁
     * @param string $val
     * @param int $keta1
     * @param string $keta2
     * @return bool
     */
    public function checkKeta($val, $keta1, $keta2 = '')
    {
        if (empty($keta2)) {
            return (mb_strlen($val) == $keta1);
        }
        return (mb_strlen($val) >= $keta1 && mb_strlen($val) <= $keta2);
    }

    /**
     * 任意の式
     * @param string $val
     * @param string $seiki
     * @return false|int
     */
    public function checkSeiki($val, $seiki)
    {
        return preg_match($seiki, $val);
    }

    /**
     * Tel
     * @param string $val
     * @return false|int
     */
    public function checkTel($val)
    {
        return preg_match('/^\d{2,5}-\d{1,4}-\d{3,4}$/', $val);
    }

    /**
     * tel2 ハイフンなし
     * @param int $val
     * @return false|int
     */
    public function checkTel2($val)
    {
        return preg_match('/^\d{10,12}$/', $val);
    }

    /**
     * tel3 ハイフンあってもなくても
     * @param string|int $val
     * @return false|int
     */
    public function checkTel3($val)
    {
        return preg_match('/^[0-9\-]{10,15}$/', $val);
    }

    /**
     * time
     * @param string|int $val
     * @return false|int
     */
    public function checkTime($val)
    {
        return preg_match('/^([0-1][0-9]|[2][0-3]):[0-5][0-9]$/', $val);
    }

    /**
     * Zip
     * @param string $val
     * @return false|int
     */
    public function checkZip($val)
    {
        return preg_match("/^\d{3}\-\d{4}$/", $val);
    }

    /**
     * @param string|int $val
     * @return false|int
     */
    public function checkZip2($val)
    {
        return preg_match("/^\d{7}$/", $val);
    }

    /**
     * url
     * @param string $val
     * @return bool
     */
    public function checkURL($val)
    {
        return (filter_var($val, FILTER_VALIDATE_URL) && preg_match('|^https?://.*$|', $val));
    }

    /**
     * mail
     * @param string $val
     * @return false|int
     */
    public function checkEmail($val)
    {
        if (strlen($val) > 100) {
            return false;
        } // すごく長かったらエラーにする
        // 「?」って使える？20150617
        return preg_match("/^[_a-z0-9-.?]+(\.[_a-z0-9-]+)*@[a-z0-9-]+([\.][a-z0-9-]+)+$/i", $val);
    }

    /**
     * domain
     * @param string $val
     * @return false|int
     */
    public function checkDomain($val)
    {
        if (strlen($val) > 100) {
            return false;
        } // すごく長かったらエラーにする
        return preg_match("/^([a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/", $val);
    }

    /**
     * IP
     * @param string $val
     * @return false|int
     */
    public function checkIP($val)
    {
        return preg_match("/^[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.[a-zA-Z]{2,}\.[a-zA-Z]{2,}$/",
            $val);
    }

    /**
     * 全角カナ
     * @param string $val
     * @param false $han_flg
     * @return false|int
     */
    public function checkKaTakana($val, $han_flg = false)
    {
        // 半角も通す。
        if ($han_flg) {
            $val = $this->han_kaku_to_jen_kaku($val);
        }
        return preg_match('/^[ァ-ヶー 　]+$/u', $val); // utf08
    }

    /**
     * ひらがな
     * @param string $val
     * @return false|int
     */
    public function checkHiragana($val)
    {
        return preg_match('/^[ぁ-ゞー　 ]+$/u', $val);
    }

    /**
     * よみがな
     * @param string $val
     * @return false|int
     */
    public function checkYomigana($val)
    {
        return preg_match('/^[ぁ-ゞー　・（）]+$/u', $val);
    }

    /**
     * 漢字(名前などに使用・漢字・ひらがな・カタカナ・英字)
     * @param string $val
     * @return false|int
     */
    public function checkKanji($val)
    {
        // 半角=>全角
        $val = mb_convert_kana($val, 'RAKSV', CHARSET);
        $val = str_replace('−', '－', $val);
        return preg_match('/^[　Ａ-Ｚａ-ｚぁ-んァ-ヶー々〇〻\x{3400}-\x{9FFF}\x{F900}-\x{FAFF}\x{20000}-\x{2FFFF}]+$/u', $val);
    }

    /**
     * 漢字(住所などに使用・漢字・ひらがな・カタカナ・英数字)
     * @param string $val
     * @return false|int
     */
    public function checkKanji2($val)
    {
        // 半角=>全角
        $val = mb_convert_kana($val, 'RANKSV', CHARSET);
        $val = str_replace('−', '－', $val);
        return preg_match('/^[　・＝０-９Ａ-Ｚａ-ｚぁ-んァ-ヶー々〇〻－\x{3400}-\x{9FFF}\x{F900}-\x{FAFF}\x{20000}-\x{2FFFF}]+$/u', $val);
    }

    /**
     * 半角英数字
     * @param string|int $val
     * @return false|int
     */
    public function checkEisu($val)
    {
        $val = mb_convert_kana($val, 'n', CHARSET);
        return preg_match("/^[0-9A-Za-z \t\/._-]+$/", $val);
    }

    /**
     * 率
     * @param int $val
     * @return bool
     */
    public function checkRate($val)
    {
        if (!preg_match("/^([1-9]\d*|0)(\.\d+)?$/", $val)) {
            return false;
        }
        if ($val > 100) {
            return false;
        }
        return true;
    }
    /**
     * 年号と日付の整合性をチェック
     * @param string $nengo
     * @param int $yy
     * @param int $mm
     * @param int $dd
     * @return bool
     */
    public function checkNengo($nengo, $yy, $mm, $dd)
    {
        if ($nengo == '' || $yy == '' || $mm == '' || $dd == '') {
            return true;
        }

        switch ($nengo) {
            case '明治':
                $yy += 1867;
                break;
            case '大正':
                $yy += 1911;
                break;
            case '昭和':
                $yy += 1925;
                break;
            case '平成':
                $yy += 1988;
                break;
        }

        // 日付の整合性
        if (!checkdate($mm, $dd, $yy)) {
            return false;
        }

        if ($nengo == '平成' && "{$yy}/{$mm}/{$dd}" >= '1989/1/8' && $yy <= date('Y')) {
            $flg = true;
        } elseif ($nengo == '昭和' && "{$yy}/{$mm}/{$dd}" >= '1925/12/25' && "{$yy}/{$mm}/{$dd}" <= '1989/1/7') {
            $flg = true;
        } elseif ($nengo == '大正' && "{$yy}/{$mm}/{$dd}" >= '1912/7/30' && "{$yy}/{$mm}/{$dd}" <= '1926/12/24') {
            $flg = true;
        } elseif ($nengo == '明治' && "{$yy}/{$mm}/{$dd}" >= '1867/7/29') {
            $flg = true;
        } else {
            $flg = false;
        }
        return $flg;
    }

    /**
     * 日付が３つに分かれた入力フォーム
     * 年・月・日すべて選択されているかどうかをチェックする.
     * @param string $year_name
     * @param string $month_name
     * @param string $day_name
     * @param string $msg
     *
     * @return bool
     */
    public function checkDate3($year_name, $month_name, $day_name, $msg)
    {
        if (empty($this->data[$year_name]) && empty($this->data[$month_name]) && empty($this->data[$day_name])) {
            return true;
        }
        if ((
                (!empty($this->data[$year_name]) && (empty($this->data[$month_name]) || empty($this->data[$day_name])))
                || (!empty($this->data[$month_name])
                    && (empty($this->data[$year_name])
                        || empty($this->data[$day_name])))
                || (!empty($this->data[$day_name])
                    && (empty($this->data[$year_name])
                        || empty($this->data[$month_name])))
            )
            || !checkdate($this->data[$month_name], $this->data[$day_name], $this->data[$year_name])
        ) {
            $this->errors[$year_name] = $msg;
            $this->errors[$month_name] = '';
            $this->errors[$day_name] = '';
        }
        return true;
    }

    /**
     * 全角に変換
     * @param string $name
     */
    public function conv_zen($name)
    {
        $this->data[$name] = mb_convert_kana($this->data[$name], 'AK', CHARSET);
    }
}
