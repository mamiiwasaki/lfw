<?php
/**----------------------------------------------------------------------------
 * Lightweight framework Rev 1.1
 *-----------------------------------------------------------------------------
 * 2006-07-24 : initial version
 * 2015-05-12 : rebuild by hide
 * 2015-05-13 : fix dc/uc recovery process by hide
 * 2016-06-18 : jbs-tools build
 * preload module
 * ----------------------------------------------------------------------------*/
// include files
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('session.gc_maxlifetime', 86400);  // 秒(デフォルト:1440)
require_once dirname(__FILE__) . '/../conf/config.inc';     // config
require_once dirname(__FILE__) . '/../inc/const.inc';       // const
require_once CLASSES_DIR . 'DataContainer.php';             // data container
require_once CLASSES_DIR . 'Factory.php';                   // factory chain
require_once CLASSES_DIR . 'ActionBase.php';                // action base
require_once CLASSES_DIR . 'UserContainer.php';             // user base class
require_once FUNCTIONS_DIR . 'debug.inc';                   // debug function
require_once FUNCTIONS_DIR . 'util.inc';                    // utilities function
//require_once VENDOR_DIR . 'autoload.php';
//require_once CLASSES_DIR . "UtilDao.php";                   // DB util

date_default_timezone_set('Asia/Tokyo');

if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('session.gc_maxlifetime', 86400);  // 秒(デフォルト:1440)
}
// メンテナンス中
if (IS_MAINTENANCE) {
    if ($_SERVER['PHP_SELF'] != '/maintenance.html') {
        header('Location: /maintenance.html');
        exit();
    }
}

// project specify config
//ORM::configure('mysql:dbname=' . DB_NAME . ';host=' . DB_HOST . ':3306;charset=utf8');
//ORM::configure('username', DB_USER);
//ORM::configure('password', DB_PASS);
//ORM::configure('caching', true);
//ORM::configure('caching_auto_clear', true);
//if (DEBUG) {
//    ORM::configure('logging', true);
//}
//// primary key が[id]以外のテーブル＆カラム名のセット
//ORM::configure('id_column_overrides', ID_COLUMN_OVERRIDES);

// SESSION START --------------------------------------------------------
session_start();

// START TIME -----------------------------------------------------------
$st_time = microtime(true);

// initialize -----------------------------------------------------------
$action = "";                                            // exec action
$module = "";                                            // exec module

// execute path
$sc = $_SERVER["SCRIPT_NAME"];                            // script name
$gc = explode("/", $sc);                                // explode url

logsave("init", "START ---------------------------------------------------------");
logsave("init", "RURI : " . $_SERVER["REQUEST_URI"]);

if (!strstr($sc, ".html")) {
    logsave("init", "file ext != .html , exit !!");
    return;
}

// check depth
if (count($gc) == 2) {                                    // /
    $module = "index";                                    // index
    $action = $gc[1];                                    // action
} else {
    // custom routing
    switch ($gc[1]) {
        default:
            $module = $gc[1];                            // folder
            $action = $gc[2];                            // action
            break;
    }
}
$action = str_replace(".html", "", $action);                // action

// DATA CONTAINER --------------------------------------------------------
$data = new DataContainer();                            // create dc
if (isset($_SESSION["data"])) {                            // recovery dc
    //logsave("init" , "DC   : Reload DC from Session");
    $data = $_SESSION["data"];
    if (gettype($_SESSION["data"]) != "object") {
        $data = new dataContainer();                    // create dc
        logsave("init", "DC   : DC != object , rebuild DC");
    }
} else {
    logsave("init", "DC   : SESSION NOT FOUND");
}
$data->setAttribute("_system", array("request_module" => $module, "request_action" => $action));

// USER CONTAINER --------------------------------------------------------
$user = new userContainer();                            // create user
if (isset($_SESSION["data"]) && gettype($_SESSION["user"]) == "object") {                            // recovery uc
    //logsave("init" , "UC   : Reload UC from Session");
    $user = $_SESSION["user"];
} else {
    logsave("init", "UC   : SESSION NOT FOUND");
}

// FACTORY ---------------------------------------------------------------
$factory = new factory($data, $user);                    // factory
$factory->request_module = $module;
$factory->request_action = $action;

// LOAD MODULE -----------------------------------------------------------
$loadfile = (MODULE_DIR . $module . ACTIONS_DIR . $action . ".php");

if (is_file($loadfile)) {
    logsave("init", "LOAD FILE:" . $loadfile);
    include_once($loadfile);
} else {
    $action = "common";
    $loadfile = (MODULE_DIR . $module . ACTIONS_DIR . $action . ".php");
    if (is_file($loadfile)) {
        logsave("init", "LOAD :" . $loadfile);
        include_once($loadfile);
    } else {
        $module = "common";
        $action = "common";
        $loadfile = (MODULE_DIR . $module . ACTIONS_DIR . $action . ".php");
        if (is_file($loadfile)) {
            logsave("init", "LOAD :" . $loadfile);
            include_once($loadfile);
        } else {
            logsave("init", "NO LOAD FILE");
            return;
        }
    }
}

// ACTION ----------------------------------------------------------------
$obj = new app_main;
$obj->initialize($data, $user, $factory);                    // initialize

if ($obj->debug_echo) {
    decho_in();
    $obj->dispatch($data, $user, $factory);                // kick dispatcher
    decho_out();
} else {
    $obj->dispatch($data, $user, $factory);                // kick dispatcher
}

// SAVE DATA CONTAINER ---------------------------------------------------
$_SESSION["data"] = $data;                                    // save dc

// SAVE USER INFO  -------------------------------------------------------
$_SESSION["user"] = $user;                                    // save ui

// RENDERING -------------------------------------------------------------
$obj->renderer($data, $user, $factory);                        // rendering

// DEBUG -----------------------------------------------------------------
if (DEBUG) {
    if ($obj->debug_status) {        // FRAME WORK STATUS
        debug_status($module, $action, substr((microtime(true) - $st_time), 0, 8));
    }

}
// MEMORY USAGE -----------------------------------------------------------
$mu = memory_get_usage(true);
$mp = memory_get_peak_usage(true);
$mu = sprintf("%5s", (int)($mu / 1024));
$mp = sprintf("%5s", (int)($mp / 1024));
// EXIT -------------------------------------------------------------------
$lap = substr((microtime(true) - $st_time), 0, 8);
logsave("init", "exit : mu = $mu kb , mp = $mp kb , LAP = $lap");
exit();
/* -- end of text ------------------------------------------------------------*/