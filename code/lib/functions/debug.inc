<?
/*-----------------------------------------------------------------------------
 *  Lightweight framework Rev 1.3
 *-----------------------------------------------------------------------------
 *  2006-07-24 : initial version
 *  2015-05-12 : rebuild by hide
 *
 *  debug functions
 *----------------------------------------------------------------------------*/
/**
 * debug status
 *
 * @param string $module /td>
 * @param string $action /td>
 * @param int    $lap    tion    /td>
 */
function debug_status($module, $action, $lap)
{
    $sn = $_SERVER["HTTP_HOST"];
    $sa = $_SERVER["SERVER_ADDR"];
    $query = $_SERVER["QUERY_STRING"];
    $cookie = $_SERVER["HTTP_COOKIE"];

    $lap = sprintf("%0.3f", $lap);
    $mu = memory_get_usage(true);
    $mu = sprintf("%5s", (int)$mu / 1024);

    echo("<table>
          <tr><td colspan='6'>FRAME WORK STATUS</td></tr>
          <tr>
           <td>STATUS</td>
           <td>Module = {$module}</td>
           <td>Action = {$action}</td>
           <td>Time = $lap sec</td>
           <td>IP = $sa</td>
           <td>&nbsp;HTTP_HOST = $sn</td>
      </tr>
          <tr>
           <td colspan='1'>COOKIE</td>
           <td colspan='3'>&nbsp;$cookie</td>
           <td colspan='1'>Memory</td>
           <td colspan='1'>&nbsp;Use $mu kb , Peek $mu kb</td>
      </tr>
          <tr>
           <td>QUERYSTR</td>
           <td colspan='6'>&nbsp;$query</td>
      </tr>
  ");
    echo("</table><br>");
}

/*
 * Debug Log write
 * SITE_DIR/status/SYSID.log.yyyymmdd
 */
function logsave($loc, $dat = '', $dat2 = '', $dat3 = '', $dat4 = '', $dat5 = '')
{
    // api _status, _orderのログは出力しない
    $sc = $_SERVER["SCRIPT_NAME"];                            // script name
    if (strpos($sc, '_status.html') !== false) {
        return;
    }
    if (IS_LOG_OUT) {
        $log = SITE_DIR . "status/" . SYSID . "-" . date("Ym", time()) . ".log";
        $sid = session_id();
        if ($sid == "") {
            $sid = "N/A";
        }
        $output_dat = '';
        if (is_array($dat) || (!empty($dat) || strcmp($dat, '') !== 0)) {
            $dat = str_replace(array("\r", "\n", "\r\n", "  ", "          "), " ", $dat);
            $output_dat .= (is_array($dat)) ? str_replace("\n", "", var_export($dat, true)) : $dat;
        }
        if (is_array($dat2) || (!empty($dat2) || strcmp($dat2, '') !== 0)) {
            $output_dat .= (is_array($dat2)) ? ", " . str_replace("\n", "", var_export($dat2, true)) : ", " . $dat2;
        }
        if (is_array($dat3) || (!empty($dat3) || strcmp($dat3, '') !== 0)) {
            $output_dat .= (is_array($dat3)) ? ", " . str_replace("\n", "", var_export($dat3, true)) : ", " . $dat3;
        }
        if (is_array($dat4) || (!empty($dat4) || strcmp($dat4, '') !== 0)) {
            $output_dat .= (is_array($dat4)) ? ", " . str_replace("\n", "", var_export($dat4, true)) : ", " . $dat4;
        }
        if (is_array($dat5) || (!empty($dat5) || strcmp($dat5, '') !== 0)) {
            $output_dat .= (is_array($dat5)) ? ", " . str_replace("\n", "", var_export($dat5, true)) : ", " . $dat5;
        }

        $s = sprintf("[%-32s][%s][%-10s] %s\n", $sid, date("m/d H:i:s"), $loc, $output_dat);

        if (php_sapi_name() == "cli") {
            $ru = "cli";
        } else {
            $ru = $_SERVER["REQUEST_URI"];
        }
        if ($ru != "/adm_system/sys_svc_status.html" && !stristr($ru, "realtime")) {
            file_put_contents($log, $s, FILE_APPEND);
        }
    }
}

/**
 * Debug Print
 *
 * @param mixed $v
 */
function pr($v)
{
    echo("<pre>");
    print_r($v);
    echo("</pre>");
}

function pr2($v)
{
    echo("<pre style='color:red;'>");
    print_r($v);
    echo("</pre>");
}

function pr3($v)
{
    echo("<pre style='color:#2c6eff;'>");
    print_r($v);
    echo("</pre>");
}

function echor($v)
{
    echo "<div style='font-weight: bold;color: red;'>$v</div>";
}

function echob($v)
{
    echo "<div style='font-weight: bold;color: #3087ff;'>$v</div>";
}
function echog($v)
{
    echo "<div style='font-weight: bold;color: #236928;'>$v</div>";
}
/*
 * PDOのプレースフォルダに値を代入したSQL文を返す
 */
function getSql($sql, $prm = array())
{
    // タグ改行除去
    $sql = str_replace("\t", "", $sql);
    $sql = str_replace(array("\r", "\n", "\r\n", "  ", "          "), " ", $sql);

    if (empty($prm)) {
        return $sql;
    }
    $arr = explode("?", $sql);
    $str = "";
    $prm2 = [];
    foreach ($prm as $val) {
        $prm2[] = str_replace(array("\r", "\n", "\r\n", "  ", "          "), " ", $val);
    }
    foreach ($arr as $key => $val) {
        if (count($prm2) != $key) {
            $str .= $val . "'" . $prm2[$key] . "'";
        } else {
            $str .= $val;
        }
    }
    return $str;
}

function sql_log($sql, $prm = array(), $loc = 'dao')
{
    logsave($loc, getSql($sql, $prm));
}
