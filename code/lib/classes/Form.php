<?php

/**-----------------------------------------------------------------------------
 *  Lightweight framework Rev 1.1
 *-----------------------------------------------------------------------------
 *	2006-07-24 : initial version ushizawa
 *	2014-06-25 : rebuild
 *
 *	formヘルパー class
 *
 * 【使用例】
 * $form->getFormTag("text", "zip", array("size='12'", "onChange=\"AjaxZip3.zip2addr(this,'','address');return false;\""));
 * $form->getFormTag("textarea", "business_hour", array("rows='4'", "cols='60'"));
 * $form->getFormTag("radio", "status", null, array(0=>"有効", 1=>"無効", 2=>"デモ");
 * $form->getFormTag("checkbox", "group_id", null, array(0=>"有効", 1=>"無効", 2=>"デモ");
 * $form->getFormTag("checkbox_uni", "group_id");
 * $form->getFormTag("file", "file1",null,null,array("image_width"=>80,"image_height"=>80));
 * $form->getFormTag("select", "pref_cd", null, $pref_arr);
 * $form->getFormTag("select", "pref_cd[]", array("multiple"), $pref_arr);  // 複数選択(multiple)
 * ------------------------------------------------
 * 第３引数 options
 *   size, rows, cols, onChange, onClick, style, class を配列で渡す。例） array("size=20", "onChange=\"test();\"", "style=\"font-size:20px;\"")
 * ------------------------------------------------
 * 第４引数 conf
 *  select_space:セレクトボックスで、1行目に空白（----）を入れる。
 *  image_width, iamge_height 添付ファイルで、画像サイズの変更をしたいときに指定する。
 *  image_preview=true	フォームPOST前に、画像のプレビューをしたいときに指定。
 *----------------------------------------------------------------------------*/

class Form
{
    /** @var array */
    public $data = [];
    /** @var array */
    public $errors = [];
    /** @var string */
    public $mode = "edit";
    /** @var string */
    public $checkbox_separator = ",";

    /**
     * フォーム要素を生成する
     * @param string $tag_type
     * @param string $name
     * @param array $options
     * @param array $list
     * @param array $conf ("select_space", "image_width", "image_height") など
     * @return string
     */
    function getFormTag($tag_type, $name = "", $options = array(), $list = array(), $conf = array())
    {
        $tag = "";
        $confirm_flg = ($this->mode == "confirm") ? true : false;
        $options_str = "";
        //$readonly = "";

        // name_str
        $name_str = "name=\"" . $name . "\"";
        // id_str
        //$id_str = "id=\"".$name."\"";
        $id_str = "id=\"" . str_replace("[]", "", $name) . "\"";    // select multiple対応
        // value_str
        if ($tag_type == "date" && !empty($this->data[$name])) {    // type="date"対策
            $this->data[$name] = date("Y-m-d", strtotime($this->data[$name]));
        }

        //if(empty($this->data[$name])){	// これだと0が空文字になってしまう。
        if (!isset($this->data[$name])) {
            $value_str = "";
        } else {
            if (!is_array($this->data[$name])) {
                /////$value_str = "value=\"".htmlspecialchars($this->data[$name])."\"";/////TODOここなんだっけ？
                //$value_str = "value=\"".$this->data[$name]."\"";
                $value_str = "value=\"" . str_replace('"', "”", $this->data[$name]) . "\"";    // 20150528 edit　「"」の対応
            } else {
                // 配列
                //$value_str = "value=\"".$this->data[$name]."\"";
            }
        }

        ///////////////////////////////////
        // オプション設定
        ///////////////////////////////////
        $option_str = "";
        /*
        // エラーがあったら、エレメントの背景に色をつける
        if(isset($this->errors[$name])) {
            //$options[] = "style=\"background-color:#FFE7DF;\"";
            $options[] = 'class="error"';
        }*/

        // 確認画面では読み取り専用
        if ($this->mode == "confirm") {
            $options[] = "READONLY";
        }
        // options string
        if (!empty($this->data[$name])) {
            // value 上書き
            if (!empty($options)) {
                foreach ($options as $key => $val) {
                    if (substr($val, 0, 6) == "value=") unset($options[$key]);
                }
            }
            // $options[] = "value=\"".$this->data[$name]."\"";
        }
        if (!empty($options)) {
            $options_str = implode($options, " ");
        }

        //// add 20140801 $optionsに「value=」があって、初回の表示の場合		
        if (empty($_REQUEST) && !empty($options) && empty($this->data[$name]) && $tag_type != "select" && $tag_type != "radio" && $tag_type != "checkbox") {
            foreach ($options as $val) {
                if (substr($val, 0, 6) == "value=") $value_str = $val;
            }
        }

        // type別
        switch ($tag_type) {
            #-------------------------------------------------------------------------
            #	password, hidden
            #-------------------------------------------------------------------------
            case "hidden":
                $tag = "<input type=\"" . $tag_type . "\" $name_str $id_str $value_str $options_str>\n";
                break;

            #-------------------------------------------------------------------------
            #	button, submit
            #-------------------------------------------------------------------------
            case "button":
            case "submit":
                // valueはオプションで。
                $tag = "<input type=\"" . $tag_type . "\" $name_str $id_str $options_str>\n";
                break;

            #-------------------------------------------------------------------------
            #	text
            #-------------------------------------------------------------------------
            case "text":
            case "date":
            case "datetime":
            default:            // 20140702 html5対応 numberとか
                if (!$confirm_flg) {
                    $tag = "<input type=\"" . $tag_type . "\" $name_str $id_str $value_str $options_str >\n";
                } else {
                    // 確認画面readonly
                    $tmp_value = (isset($this->data[$name])) ? htmlspecialchars($this->data[$name]) : "";//////TODOなんで？
                    $tmp_value = (isset($this->data[$name])) ? ($this->data[$name]) : "";

                    $tag = "<input type=\"hidden\" $name_str $id_str $value_str $options_str>\n";
                    $tag .= "<span style='line-height:16px;'>" . $tmp_value . "</span>\n";
                }
                break;

            #-------------------------------------------------------------------------
            #	password
            #-------------------------------------------------------------------------
            case "password":
                if (!$confirm_flg) {
                    $tag = "<input type=\"" . $tag_type . "\" $name_str $id_str $value_str $options_str autocomplete=\"off\">\n";
                } else {

                    // 確認画面readonly
                    $tmp_value = (!empty($this->data[$name])) ? str_repeat("*", strlen($this->data[$name])) : "";

                    $tag = "<input type=\"hidden\" $name_str $id_str $value_str $options_str>\n";
                    $tag .= "<span style='line-height:16px;'>" . $tmp_value . "</span>\n";
                }
                break;

            #-------------------------------------------------------------------------
            #	textarea
            #-------------------------------------------------------------------------
            case "textarea":

                if (!$confirm_flg) {
                    if (!empty($this->data[$name])) {
                        $tag = "<textarea $name_str $id_str $options_str>" . $this->data[$name] . "</textarea>";
                    } else {
                        $tag = "<textarea $name_str $id_str $options_str></textarea>";
                    }
                } else {
                    // 確認画面readonly
                    $tag .= "<input type=\"hidden\" $name_str $id_str $value_str $options_str>\n";
                    //$tag .= "<div style='width:100%;height:100%;border:solid 1px #999999;overflow-y:scroll;border:none;'>";
                    $tag .= "<div>";// ↑これだと、直下にある登録ボタンが効かなくなった！
                    if (!empty($this->data[$name])) {
                        $tag .= nl2br(htmlspecialchars($this->data[$name], ENT_QUOTES, CHARSET));
                    }
                    $tag .= "</div>\n";
                }
                break;

            #-------------------------------------------------------------------------
            #	readonly
            #-------------------------------------------------------------------------
            case "readonly":
                /*
                                $tag .= "<input type=\"hidden\" $name_str $id_str $value_str $options_str>\n";
                                if(isset($this->data[$name]))
                                $tag .= "<span id='".$name."_readonly'>".$this->data[$name]."</span>";
                                */
                // 20150603 edit　spanだとJsで.value='XX' とかできない
                $tag .= "<input type=\"text\" $name_str $id_str $value_str $options_str style='border:none;' readonly>\n";
                break;

            #-------------------------------------------------------------------------
            #	select
            #-------------------------------------------------------------------------
            case "select":
//echoblue($name."---".$this->data[$name]);
//pr2($this->data);
                /******
                 * multiple対策！([]の配列で値が渡される）
                 */
                $name2 = str_replace("[]", "", $name);

                if (!$confirm_flg) {

                    $tag = "<select $name_str $id_str $options_str >\n";

                    if (!empty($list)) {

                        if (!isset($conf["select_space"]) || $conf["select_space"] != false) {
                            $tag .= "<OPTION value=\"\" selected>---</OPTION>\n";
                        }
                        // option生成
                        foreach ($list as $key => $val) {
                            // 選択状態
                            //if(isset($this->data[$name]) && (strcmp($key, $this->data[$name])==0)){

                            // }
                            // multiple(複数選択[]）のときの選択状態
                            if (isset($this->data[$name2]) && is_array($this->data[$name2]) && in_array($key, $this->data[$name2])) {
                                $selected = "SELECTED";

                                // 普通のセレクトボックスの選択状態
                            } else if (isset($this->data[$name]) && !is_array($this->data[$name]) && strcmp($key, $this->data[$name]) == 0) {
                                $selected = "SELECTED";

                                // 非選択状態       
                            } else {
                                $selected = "";
                            }

                            //$tag .= "<OPTION value=\"$key\" $selected>$val</OPTION>\n";
                            $tag .= "<OPTION value=\"$key\" id=\"$name$key\" $selected>$val</OPTION>\n";
                        }
                    }
                    $tag .= "</select>\n";

                } else {

                    if (isset($this->data[$name2]) && is_array($this->data[$name2])) {
                        foreach ($this->data[$name2] as $v) {
                            $arr[] = $list[$v];
                        }
                        $confirm_view = (implode("<br>", $arr));
                        // multipleは配列なので、カンマ区切りでデータを渡す！
                        $value_str = "value=\"" . implode(",", $this->data[$name2]) . "\"";

                    } else {
                        //	pr($list);
                        $confirm_view = (!empty($this->data[$name])) ? $list[$this->data[$name]] : "";
                        $value_str = (isset($this->data[$name])) ? "value=\"" . $this->data[$name2] . "\"" : "";
                    }

                    // 確認画面readonly
                    //$tag .= "<input type=\"text\" $name_str $id_str $value_str $options_str>";	//////option_strいるか？？？？！！
                    $tag .= "<input type=\"hidden\" $name_str $id_str $value_str>";    //////option_strいるか？？？？！！
                    $tag .= "<span style='line-height:20px;'>{$confirm_view}</span>\n";
                }

                break;

            #-------------------------------------------------------------------------
            #	radio
            #-------------------------------------------------------------------------
            case "radio":

                // エラーだったら背景に色をつける
                $error_bg = (!empty($this->errors[$name])) ? "style='background-color:#FFE7DF;'" : "";
                $tag = "<span $error_bg>";

                if (!$confirm_flg) {
                    if (!empty($list)) {
                        foreach ($list as $key => $val) {
                            $id = $name . $key;
                            $checked = (isset($this->data[$name]) && strcmp($key, $this->data[$name]) == 0) ? "CHECKED" : "";    // 選択

                            $tag .= "<label for=\"{$id}\"><input type=\"radio\" $name_str id=\"{$id}\" value=\"{$key}\" $options_str $checked/>";
                            $tag .= $val . "</label> \n";
                        }
                    }
                } else {
                    // 確認画面readonly
                    if (isset($this->data[$name])) $tag .= $list[$this->data[$name]];
                    $tag .= "<input type=\"hidden\" $name_str $id_str $value_str $options_str>\n";
                }
                $tag .= "</span>";
                break;

            #-------------------------------------------------------------------------
            #	checkbox
            #-------------------------------------------------------------------------
            case "checkbox":

                //if(empty($list)) return "配列を指定してください";
                if (empty($list)) $list = array(1 => "");    // 20150113 edit

                $tag = "";

                // ★編集画面--------------------------------
                if (!$confirm_flg) {

                    // エラーだったら背景に色をつける
                    $error_bg = (!empty($this->errors[$name])) ? "style='background-color:#FFE7DF;'" : "";
                    if (count($list) > 1) $tag = "<span $error_bg>";

                    foreach ($list as $key => $val) {
                        $name_str = "name=\"" . $name . "[]\"";
                        $id_str = "id=\"" . $name . $key . "\"";

                        // checked
                        $checked = "";
                        if (!empty($this->data[$name])) {
                            // カンマ区切りの場合配列にする
                            if (!is_array($this->data[$name]))
                                //$this->data[$name] = explode(",", $this->data[$name]);

                                //	$this->data[$name] = substr($this->data[$name], 1, strlen($this->data[$name])-2);	// 前後の区切り文字を除去 20150514 add
                                $this->data[$name] = explode($this->checkbox_separator, $this->data[$name]);

                            // checked
                            if (in_array($key, $this->data[$name]))
                                $checked = "checked";
                        }

//						$tag.= "<input type=\"checkbox\" $name_str $id_str value=\"{$key}\" $checked/>";
//						$tag.= "<label for=\"".$name.$key."\">".$val."</label> \n";

                        $tag .= "<label for=\"" . $name . $key . "\">";
                        $tag .= "<input type=\"checkbox\" $name_str $id_str value=\"{$key}\" $options_str $checked/>";
                        $tag .= $val . "</label> \n";
                    }
                    if (count($list) > 1) $tag .= "</span>\n";

                    // ★確認画面 --------------------------------
                } else {

                    $value_str = "";
                    if (!empty($this->data[$name])) {
                        /*
                        if(!is_array($this->data[$name])) {
                            // 一つもチェックされていない
                            $value_str = "value=\"\"";
                        } else {
                            foreach($this->data[$name] as $val) $keys[] = $list[$val];
                            $tag.= implode(",　", $keys);
                            $value_str = "value=\"". implode(",", $this->data[$name]) ."\"";							
                        }																
                        */

                        // チェックボックスは配列で値が入ってくるので、カンマ区切りで文字列に変換する
                        if (is_array($this->data[$name])) {
                            foreach ($this->data[$name] as $val) $keys[] = $list[$val];
                            $view_str = implode(",　", $keys);
                            $tag .= $view_str;
                            if (empty($view_str)) $tag .= "<img src='./images/common/check.png'>";

                            //$value_str = "value=\"". implode(",", $this->data[$name]) ."\"";
                            // 前後に区切り文字を追加20150518
                            //$value_str = "value=\"". implode($this->checkbox_separator, $this->data[$name])."\"";
                            //$value_str = "value=\"". $this->checkbox_separator.implode($this->checkbox_separator, $this->data[$name]) .$this->checkbox_separator."\"";
                            // やっぱり前後に区切り文字を入れるのはやめ。
                            $value_str = "value=\"" . implode($this->checkbox_separator, $this->data[$name]) . "\"";

                        }
                    }
                    $tag .= "<input type=\"hidden\" $name_str $id_str $value_str />\n";

                }
                break;
            #-------------------------------------------------------------------------
            #	checkbox_uni
            #-------------------------------------------------------------------------
            case "checkbox_uni":

                $tag = "";

                // ★編集画面--------------------------------
                if (!$confirm_flg) {

                    // エラーだったら背景に色をつける
                    $error_bg = (!empty($this->errors[$name])) ? "style='background-color:#FFE7DF;'" : "";

                    //$name_str = "name=\"".$name."[]\"";
                    //$id_str = "id=\"".$name.$key."\"";

                    // checked
                    $checked = "";
                    if (!empty($this->data[$name])) {
                        // checked
                        if ($this->data[$name] == 1) {
                            $checked = "checked";
                        }
                    }
                    $tag .= "<input type=\"checkbox\" $name_str $id_str value=\"1\" $options_str $checked/>";

                    // ★確認画面 --------------------------------
                } else {

                    $value_str = "";
                    if (!empty($this->data[$name])) {
                        $value_str = "value='1'";
                        $tag .= "＊︎";
                    }
                    $tag .= "<input type=\"hidden\" $name_str $id_str $value_str />\n";
                }
                break;

            #-------------------------------------------------------------------------
            #	file
            #-------------------------------------------------------------------------
            case "file":
                // アップロードディレクトリ
                $upload_dir = DOC_DIR . "/upload/";
                $tmp_dir = $upload_dir . "tmp/";
                $img_src = "/upload/";
                $tmp_src = $img_src . "tmp/";

                if (!file_exists($upload_dir)) {
                    echo "<font color='red'>ファイルアップロード用のディレクトリがありません<br><br>$upload_dir</font><br>";
                    exit;
                }
                $value = "";
                $value_tmp = "";
                $value_del = "";

                if (!file_exists(FUNCTIONS_DIR . "gdthumb.php")) {
                    echo "<font color='red'>画像のコンバートに必要なgdthumb.phpがありません！！</font><br>";
                    exit;
                }

                // $_FILESに値が入っていたら、実行してしまう。
                // validatorでエラーになりconfirm画面に遷移しない場合、空になってしまうから。
                if (!empty($_FILES[$name]['name'])) {
                    $tmp_name = $_FILES[$name]['tmp_name'];
                    $ext = strrchr($_FILES[$name]['name'], "."); // 拡張子
                    //$file_tmp = str_replace(" ","",$name."_".time().$ext);	// ★name + time + 拡張子
                    $filename = str_replace(" ", "", $name . "_" . time() . $ext);    // ★name + time + 拡張子
                    $size = $_FILES[$name]["size"];

                    $this->data[$name . "_tmp"] = $filename;
                    //echo $tmp_name."----".$tmp_dir.$filename."<br>";exit;			
                    ////////////////////////////////////////////////////
                    // ファイルの移動　tmpディレクトリに格納する
                    ////////////////////////////////////////////////////
                    if (!file_exists($tmp_dir . $filename)) {
                        if (!move_uploaded_file($tmp_name, $tmp_dir . $filename)) {
                            die("<font color='red'>ファイルの移動に失敗しました(move_uploaded_file)<br>ファイルサイズが大きすぎるか、formのenctypeは\"multipart/form-data\"になってるか、ディレクトリが存在するか確認してください</font>");
                        }

                        ////////////////////////////////////////////////////
                        // 幅、高さの指定があったらconvert!!
                        ////////////////////////////////////////////////////
                        if (!empty($this->data[$name . "_width"]) || !empty($this->data[$name . "_height"])) {
                            require_once FUNCTIONS_DIR . 'gdthumb.php';
                            // リサイズ画像の出力
                            $w = (!empty($this->data[$name . "_width"])) ? $this->data[$name . "_width"] : "";
                            $h = (!empty($this->data[$name . "_height"])) ? $this->data[$name . "_height"] : "";

                            gdthumb($tmp_dir . $filename, $w, $h, $tmp_dir . $filename, false);

                        } // if convert

                    } // !file_exists

                    $this->data[$name . "_tmp"] = $filename;
                } // !empty file
                else if (!empty($this->data[$name])) {
                    $this->data[$name . "_tmp"] = $this->data[$name];
                }
                if (!empty($this->data[$name . "_tmp"])) {
                    $value_str = "value=\"" . $this->data[$name . "_tmp"] . "\"";
                    $value_tmp_str = "value=\"" . $this->data[$name . "_tmp"] . "\"";
                } else {
                    $value_str = "value=''";
                    $value_tmp_str = "value=''";
                }

                //pr($this->data);exit;
                // ★編集画面 ///////////////////////////////////////
                if (!$confirm_flg) {
//pr($conf);
                    /////////$tag = "<input type=\"file\" $name_str $id_str>\n";						// file
                    // 20150529アップロード前に画像のプレビューを表示する
                    // リサイズしていあったら、合わせる
                    if ($conf["image_preview"]) {
                        $tag = "<input type=\"file\" $name_str $id_str onchange=\"preview(this, '{$name}');\">";
                    } else {
                        $tag = "<input type=\"file\" $name_str $id_str>";
                    }
                    /*
                                        // preview
                                        $size = "";
                                        if(!empty($conf["image_width"]) || !empty($conf["image_height"])){
                                            $size = "style=\"border:1px solid #000;";
                                        }
                                        
                                        $size .= (!empty($conf["image_width"]))? "width:".$conf["image_width"]."px;" : "";
                                        $size .= (!empty($conf["image_height"]))? " height:".$conf["image_height"]."px;" : "";
                                        //$tag .= "<div id='preview_field{$name}' {$size}>aaa</div>";					// file
                                        $tag .= "<div id='preview_field{$name}'}></div>";					// file
                                        $tag.= $size;
                                        */
                    $tag .= "<div id='preview_field{$name}'}></div>";
                    // tmp
                    $tag .= "<input type=\"hidden\" name=\"{$name}_tmp\" $value_tmp_str>";    // tmp ////////////////////

                    // 削除チェックボックス
                    if (!empty($this->data[$name . "_tmp"])) {
                        $checked = (!empty($this->data[$name . "_del"])) ? "CHECKED" : "";
                        $tag .= "<input type=\"checkbox\" name=\"{$name}_del\" value=1 $checked><b>削除</b>";
                    }

                    // ★確認画面 ///////////////////////////////////////
                } else {

                    $tag .= "<input type=\"hidden\" $name_str $id_str $value_str>";        // file////////////////////
                    $tag .= "<input type=\"hidden\" name=\"{$name}_tmp\" $value_tmp_str>";    // file_tmp////////////////////
                    // 削除チェックボックス
                    if (!empty($this->data[$name . "_del"]) && $this->data[$name . "_del"] == 1) {
                        $tag .= "<input type=\"hidden\" name=\"{$name}_del\" value=1>";
                        $tag .= "<img src='/images/check.png'><span style='color:red;font-weight:bold;'>削除</span>";
                    }
                }

                ///////////////////////////////////////////////////
                // if($conf["image_preview"]) で、画像ファイルだったらイメージの表示。
                // それ以外はアイコンを表示
                // リンクを追加
                //if((!empty($this->data[$name]) || !empty($this->data[$name."_tmp"])) && empty($this->data[$name."_del"])) {
                if ((!empty($this->data[$name]) || !empty($this->data[$name . "_tmp"]))) {
                    // file
                    $file = (!empty($this->data[$name . "_tmp"])) ? $this->data[$name . "_tmp"] : $this->data[$name];

                    // src
                    $src = "";
                    if (file_exists($tmp_dir . $file)) {
                        $src = $tmp_src . $file;    // upload下
                    } else if (file_exists($upload_dir . $file)) {
                        $src = $img_src . $file;    // upload/tmp下
                    }
                    // link
                    //if(preg_match('/\.gif$|\.png$|\.jpg$|\.jpeg$|\.bmp$/i', $file)){
                    //$link = "<img src='{$src}'>".$file ;
                    //} else {
                    //	$link = get_img_icon($file).$file;
                    //}
                    // 画像プレビュー設定があり、画像ファイルだったらイメージ表示
                    if ($conf["image_preview"] && preg_match('/\.gif$|\.png$|\.jpg$|\.jpeg$|\.bmp$/i', $file)) {
                        $tag .= "<img src='{$src}'>";

                    } else {
                        if (preg_match('/\.gif$|\.png$|\.jpg$|\.jpeg$|\.bmp$/i', $file)) {
                            $tag .= "<a href='#' onClick=\"window.open('{$src}');return false;\">" . $file . "</a>";
                        } else {
                            $tag .= "　<a href='{$src}'>" . $file . "</a>";
                        }
                    }

                } else {

                }

                ///////////////////////////////////////////////////
                // イメージの幅指定がある場合、hiddenで値を渡すためのタグ。
                // 20140812 add
                if (!empty($conf["image_width"])) $tag .= $this->getFormTag("hidden", $name . "_width", array("value=" . $conf["image_width"]));
                if (!empty($conf["image_height"])) $tag .= $this->getFormTag("hidden", $name . "_height", array("value=" . $conf["image_height"]));

                ///////////////////////////////////////////////////
                // サイズチェックのためにサイズを、hiddenで値を渡すためのタグ。
                // 20160118 add
                if (!empty($size) && empty($this->data[$name . "_size"])) $this->data[$name . "_size"] = $size;
                $tag .= $this->getFormTag("hidden", $name . "_size");

                break;

        } // switch case
        // encode 

        // 入力確認で、エラーがあったらTDを赤くするクラス
        if (isset($this->errors[$name])) {
            //$options[] = "style=\"background-color:#FFE7DF;\"";
            $tag .= "<span class=\"error\"></span>";
        }
        return $tag;
    }


    //-------------------------------------------------------------------------
    //	更新ボタン
    //-------------------------------------------------------------------------
    /*function submit_btn($style="", $regist_js="")	{
        // style
        if(empty($style)) $style = "font-size:18px;padding:5px;font-weight:bold;";
        //this.form.submit()
        if($this->mode == "edit")	{
            $str = $this->getFormTag("button", "", array("value='入力内容を確認する'", "onClick=\"{$regist_js}this.form.submit();\"", "class=\"btn_regist\""));
        } else {
            $str = $this->getFormTag("button", "", array("value='戻る'", "onClick=\"this.form.back_flg.value=1;this.form.submit();\"", "class='fncbtn'"))."　　";
            $str.= $this->getFormTag("button", "", array("value='この内容で登録'", "onClick=\"{$regist_js}this.form.submit();\"", "style='".$style."'", "class='btn_regist'"));
            $str.= "<div id=\"submit_msg\">この内容で登録します。よろしければ、【登録】ボタンをクリックしてください.</div>";
        }
        return $str;
    }*/
    function submit_btn($regist_js = "")
    {

        if ($this->mode == "edit") {
            $str = "<span class='btn_submit'><a href='#' onClick=\"{$regist_js}document.frm.submit();\">入力内容を確認する</a></span>";
        } else {
            $str = "<span class='btn_back'><a href='#' onClick=\"document.frm.back_flg.value=1;document.frm.submit();\">戻　る</a></span>\n";
            $str .= "<span class='btn_submit'><a href='#' onClick=\"document.frm.submit();\">この内容で登録</a></span>\n";
            $str .= "<div id=\"submit_msg\" style='margin-top:10px;'>この内容で登録します。よろしければ、【登録】ボタンをクリックしてください.</div>\n";
        }
        return $str;
    }


    //-------------------------------------------------------------------------
    //	本日ボタン
    //-------------------------------------------------------------------------
    function today_btn($name)
    {
        if ($this->mode == "confirm") return "";
        return "<input type='button' value='本日' onClick=\"putDate('{$name}');\">";
    }


    //-------------------------------------------------------------------------
    //	今月ボタン
    //-------------------------------------------------------------------------
    function thismonth_btn($start = "start", $end = "end", $submit = false)
    {
        $start_date = date("Y-m-01", time());
        $end_date = date("Y-m-t", time());

        $onclick = "$('{$start}').value='{$start_date}';$('{$end}').value='{$end_date}';";
        if ($submit) $onclick .= "this.form.submit();";


        if ($this->mode == "confirm") {
            return "";
        } else {
            return "<input type='button' value='今月' onClick=\"$onclick\" $js>";
        }
    }

    /**
     *
     *
     * function getImage($file){
     * return "<img src='/upload/".$this->tablename."/".$file."' border='0'>";
     * } */


    function getLimit()
    {
        for ($i = 10; $i <= 50; $i += 10) {
            $arr[$i] = $i;
        }
        $arr[80] = 80;
        $arr[100] = 100;
        $arr[150] = 150;
        $arr[200] = 200;

        return $this->getFormTag("select", "limit", array("onChange=\"this.form.submit();\""), $arr, array("select_space" => false)) . "件表示　";
    }


}
/*
						////////////////////////////////////////////////////
						// 幅、高さの指定があったらconvert!!
						////////////////////////////////////////////////////
						if(!empty($this->data[$name."_width"]) || !empty($this->data[$name."_height"])) {
							require_once FUNCTIONS_DIR.'gdthumb.php';
							// リサイズ画像の出力
							gdthumb($tmp_dir.$file_tmp, $this->data[$name."_width"], $this->data[$name."_height"], $tmp_dir.$file_tmp, false);
								
							convert入っていない
							$scale = $this->data[$name."_width"]."x".$this->data[$name."_height"];
							exec("/usr/bin/convert ".$tmp_dir.$file_tmp." -resize {$scale} ".$tmp_dir.$file_tmp, $ret, $out);
							//echo("/usr/bin/convert ".$tmp_dir.$file_tmp." -resize {$scale} ".$tmp_dir.$file_tmp);
							if($ret==1)	{
								die( $out);
							}						
						} // if convert
*/


/* -- end of text ------------------------------------------------------------*/
