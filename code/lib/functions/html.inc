<?php
/*-----------------------------------------------------------------------------
 * Lightweight framework Rev 1.3
 *-----------------------------------------------------------------------------
 * 2006-07-24 : initial version
 * 2015-05-12 : rebuild by hide
 *
 * html utilities
 *----------------------------------------------------------------------------*/

/**
 * input select
 * @param $name
 * @param $selected_value
 * @param $list
 * @param bool $incrude_empty
 * @param string $label_class
 * @param string $top_str
 * @return string
 */
function makeSelect($name, $selected_value, $list, $incrude_empty = true, $label_class = '', $top_str = '---')
{
    // option
    $option_str = (!empty($options)) ? implode(' ', $options) : null;
    $html = "";
    $html .= "<label class='c-form__selectwrap {$label_class}'>";
    $html .= "<select name='{$name}' $option_str>";
    if ($incrude_empty) {
        $html .= "<option value=''>{$top_str}</option>";
    }
    if (!empty($list)) {
        foreach ($list as $k => $v) {
            if (strcmp($selected_value, $k) == 0) {
                $html .= "<option value='$k' selected>" . h($v) . '</option>';
            } else {
                $html .= "<option value='$k' >" . h($v) . '</option>';
            }
        }
    }
    $html .= '</select></label>';
    return $html;
}

/**
 * input radio
 * @param $name
 * @param $select_value
 * @param $list
 * @param string $label_class
 * @return string
 */
function makeRadio($name, $select_value, $list, $label_class = 'p-mypage__form__selectCountry')
{
    $html = "<span>"; // エラークラス不可するのに必要
    if (!empty($list)) {
        foreach ($list as $key => $val) {
            $checked = (strcmp($key, $select_value) == 0) ? "checked" : '';
            $html .= "<label class='{$label_class} radio_label {$checked}'>";
            $html .= "<input type='radio' name='{$name}' value='{$key}' {$checked} class='radio'>{$val}</label>\n";
        }
    }
    return $html . "</span>";
}

/**
 * input checkbox array
 * @param $name
 * @param $values
 * @param $list
 * @return string
 */
function makeCheckboxArray($name, $values, $list)
{
    // 配列に。（DBにはカンマ区切りで登録されている）
    if (!empty($values) && !is_array($values)) {
        $values = explode(',', $values);
    }
    $html = "<span>"; // エラークラス不可するのに必要
    if (!empty($list)) {
        foreach ($list as $key => $val) {
            $checked = (is_array($values) && in_array($key, $values)) ? 'CHECKED' : ''; // checked
            $html .= "<label><input type='checkbox' name='{$name}[]' value='{$key}' {$checked}>";
            $html .= h($val) . "</label>\n";
        }
    }
    // 未選択時対応 このhiddenは必ず最後に印字
    $html .= "<input type='hidden' name='{$name}[]' value=''></span>";
    return $html;
}

/**
 * input makeCheckbox
 * @param $name
 * @param $values
 * @param $str
 * @return string
 */
function makeCheckbox($name, $values, $str)
{
    $html = "<input type='hidden' name='{$name}' value='' class='dummy'>";    // 未選択時対応 このhiddenは必ず先に印字
    $checked = (!empty($values) && $values == '1') ? 'CHECKED' : ''; // 選択
    $html .= "<label><input type='checkbox' name='{$name}' value='1' {$checked}> {$str}</label>";
    return $html;
}

/**
 * input file
 * @param $key
 * @param $val
 * @param $table
 * @param string $accept
 * @param string $width
 * @return string
 */
function makeFile($key, $val, $table, $accept = '', $width = '')
{
    // path
    $file_path = UPLOAD_DIR . "{$table}/{$val}";
    $flg = (!empty($val) && file_exists($file_path)) ? true : false;
    if (!$flg) {
        // 未登録
        $tag = "<input type='file' name='{$key}' accept='{$accept}'>
                <input type='hidden' name='{$key}_tmp'>";
    } else {
        // 登録済み
        $tag = " <a href='/file.html?no_cache=1&dir={$table}&file={$val}' target='_blank'>{$val}</a>
                <div class='btn btn_delete js_delete_file' data-colname='{$key}' data-table='{$table}'>ファイル削除</div>
               ";
    }
    // 幅指定してあったら
    if (!empty($width)) {
        $tag .= "<input type='hidden' name='{$key}_width' value='{$width}'>";
    }
    return $tag;
}

/**
 * フラグからチェック画像を返す
 * @param string $val
 * @param int $checked
 * @return string
 */
function getCheckImg($val = "", $checked = 1)
{
    return (!empty($val) && $val == $checked) ? "<img src='../common/img/check.png'>" : "";
}
