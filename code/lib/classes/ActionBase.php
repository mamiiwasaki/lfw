<?php
/*-----------------------------------------------------------------------------
 *  Lightweight framework Rev 1.1
 *-----------------------------------------------------------------------------
 *  2006-07-24 : initial version
 *  2009-10-31 : rebuild
 *  2011-05-10 : rebuild by hide
 *  2015-05-12 : rebuild by hide
 *
 *  action base
 *----------------------------------------------------------------------------*/

class ActionBase
{
    //public $me = [];
    /** @var array<int, string>  */
    public $viewitem = [];
    /** @var array<int, string>  */
    protected $template = [];
    /** @var string  */
    protected $tplpath = null;
    /** @var bool  */
    public $debug_echo = false;
    /** @var bool  */
    public $debug_status = false;
    //public $debug_query = false;
    /** @var string  */
    public $maintitle = "N/A";
    /** @var string  */
    public $subtitle = "N/A";
    /** @var bool  */
    public $nooutput = false;

    /**
     * dispatcher
     * @param dataContainer $data
     * @param userContainer $user
     * @param factory $factory
     * @return bool
     */
    public function dispatch(dataContainer $data, userContainer $user, factory $factory)
    {
        return true;
    }

    /**
     * renderer
     * @param dataContainer $data
     * @param userContainer $user
     * @param factory $factory
     */
    public function renderer(dataContainer $data, userContainer $user, factory $factory)
    {
        // no output mode
        if ($this->nooutput) {
            return;
        }
        // default item
        $this->viewitem["LOCATION"] = $_SERVER["SCRIPT_FILENAME"];
        $this->viewitem["SERVER"] = $_SERVER["SERVER_NAME"];
        $this->viewitem["NOW"] = date("Y/m/d H:i:s");
        $this->viewitem['CONTENTS_NAME'] = CONTENTS_NAME;
        $this->viewitem['RELEASE'] = RELEASE;
        if (empty($this->viewitem['FREE_WORD_HEADER'])) {
            $this->viewitem['FREE_WORD_HEADER'] = '';
        }
        if (DEBUG) {
            $this->viewitem['GA_TAG'] = '';
        } else {
            $this->viewitem['GA_TAG'] = GA_TAG;
        }

        // load basefile
        $buff = implode("", file(realpath($_SERVER["SCRIPT_FILENAME"])));
        // viewitem remap
        $akey = array_keys($this->viewitem);
        for ($i = 0; $i < count($akey); ++$i) {
            $rpl = "___" . $akey[$i] . "___";
            $buff = str_replace($rpl, $this->viewitem[$akey[$i]], $buff);
        }
        // output
        echo($buff);
    }

    /**
     * micro template : path set
     * @param string $path
     */
    public function pathset_tpl($path)
    {
        $this->tplpath = $path;
    }

    /**
     * micro template : fetch template
     * @param string $file
     */
    public function fetch_tpl($file)
    {
        $path = $this->tplpath;
        $buff = implode("", file($path . $file));
        $key = str_replace(".html", "", $file);
        $this->template[$key] = $buff;
    }

    /**
     * micro template : assign template
     * @param string $key
     * @param array<string|ingeter> $item
     * @return mixed|string|string[]
     */
    public function assign_tpl($key, $item)
    {
        $buff = $this->template[$key];
        $akey = array_keys($item);
        for ($i = 0; $i < count($akey); ++$i) {
            $rpl = "___" . $akey[$i] . "___";
            $buff = str_replace($rpl, $item[$akey[$i]], $buff);
        }
        return $buff;
    }
}
