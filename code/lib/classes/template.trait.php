<?php

/**-----------------------------------------------------------------------------
 * Lightweight framework Rev 1.1
 *-----------------------------------------------------------------------------
 * 2006-07-24 : initial version
 * 2009-10-31 : rebuild
 * 2011-05-10 : rebuild by hide
 * 2015-05-12 : rebuild by hide
 *
 * template Trait
 *----------------------------------------------------------------------------*/

trait templateTrait
{
    public $template = array();
    public $template_body = array();

    /**
     * micro template : init template
     * @param string $tpfile
     */
    function init_tpl($tpfile)
    {
        global $module;
        $loadfile = MODULE_DIR . $module . TEMPLATE_DIR_NAME . $tpfile;
        if (!file_exists($loadfile)) {
            echo "テンプレートファイルがありません。<br>" . $loadfile . "<br>";
        }
        $this->template_body = array();
        if (is_readable($loadfile)) {
            $this->template_body = file($loadfile);
        }
    }

    /**
     * micro template : fetch template
     * @param $kw
     * @return string
     */
    function fetch_tpl($kw)
    {
        $key = "<!--##" . $kw . "-->";        //	TRIGGER
        $intmpl = false;
        $buff = array();
        foreach ($this->template_body as $line) {
            $intmpl = (trim($line) == $key) ? !$intmpl : $intmpl;
            if ($intmpl) {
                $buff[] = $line;
            }
        }
        return implode("", $buff);
    }

    /**
     * micro template : assign template
     * @param $buff
     * @param $items
     * @return string|string[]
     */
    function assign_tpl($buff, $items)
    {
        if ($items == "" || !is_array($items)) {
            return $buff;
        }

        $akey = array_keys($items);
        foreach ($akey as $key) {
            $buff = str_replace($rpl = "___" . $key . "___", $items[$key], $buff);
        }
        return $buff;
    }
}
