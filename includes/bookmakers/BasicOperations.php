<?php

class BasicOperations
{
    public $BK_ID;
    public $tab_num;
    public $_window_handles = null;
    private $_browsers_count;

    public function checkActiveTab($debug = false)
    {
        if ($this->BK_ID == "PIN") {
            return;
        }
        if (!isset($this->tab_num)) {
            return;
        }
        global $WD__CurrentWindowHandle;


        if (!$this->_window_handles) {
            $this->_window_handles = WD()->getWindowHandles();
        }

        $this->_browsers_count = count($this->_window_handles);

        if ($debug) {
            print_r(WD()->getWindowHandles());
        }

        if ($this->tab_num > $this->_browsers_count) {
            WD()->selectWindow($this->_window_handles[$this->_browsers_count - 1]);

            for ($i = 0; $i < ($this->tab_num - $this->_browsers_count); $i++) {
                WD()->execute("window.open('about:blank','_blank');");
                $this->_window_handles = WD()->getWindowHandles();
                if ($debug) {
                    echo "[+] Opening window... \n";
                }
                sleep(1);
            }
            $this->_browsers_count += 1;
        }


        if ($debug) {
            echo __("[#] WINDOW BEFORE: %s\n", WD()->getWindowHandle());
        }

        if (!$WD__CurrentWindowHandle) {
            WD()->selectWindow($this->_window_handles[0]); // При первом запуске активного окна нет.
            $WD__CurrentWindowHandle = WD()->getWindowHandle();
        }
        if ($WD__CurrentWindowHandle != $this->_window_handles[($z = $this->tab_num - 1)]) {
            $this->_window_handles = WD()->getWindowHandles(); // Актуализируем список вкладок
            WD()->selectWindow($this->_window_handles[$z]);
            $WD__CurrentWindowHandle = $this->_window_handles[$z];
        }

        if ($debug) {
            echo __("[#] WINDOW AFTER: %s\n", WD()->getWindowHandle());
        }
        if ($debug) {
            print_r(WD()->getWindowHandles());
        }

        return true;
    }

    function navigate($url, $js_wait = true)
    {
        $this->checkActiveTab();

        WD()->execute("window.location.href = 'about:blank';");

        $t1 = microtime(true);

        $r = WD()->setTimeouts('pageLoad', 120000);
        $r = WD()->setTimeouts('script', 15000);
        $r = WD()->setTimeouts('implicit', 500);

        if (WD()->browser == 'firefox') {
            _usleep(0.3);
        }

        WD()->execute("setTimeout(function(){ window.location.href = '$url'; }, 100);");

        $t2 = microtime(true);
        return sprintf("%.2f", $t2 - $t1);
    }

    function waitForLoad($required_selector = 'body')
    {
        $this->checkActiveTab();

        WD()->setTimeouts('script', 15000);
        $max_wait_for_load = 25;
        $st = microtime(true);
        $is_timeout = false;

        do {
            $is_ready = WD()->execute("
                d=document;
                if (d.readyState === 'complete') {
                    if(!window.jQuery){
                        window.jQuery = true;
                        var s = d.createElement('script');
                        s.src = 'https://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js';
                        s.type = 'text/javascript';
                        d.body.appendChild(s);
                    }else{
                        if(typeof $ === 'function' && $('$required_selector').length > 0 && $(document).ready()){
                            return true;
                        }
                    }
                }
                return false;
            ");

            if (is_object($is_ready)) {
                _usleep(0.10);
                continue;
            }

            if ($is_ready) {
                break;
            }
            _usleep(0.10);
        } while (!($is_timeout = microtime(true) - $st > $max_wait_for_load));

        if ($is_timeout) throw new Exception;

        return;
    }

    public function js($code, $async = false, $args = array())
    {
        $m = $async ? "execute_async" : "execute";
        if ($m == 'execute_async') {
            $code
                = "
                on_finished = arguments[arguments.length - 1];

                $code;

                on_finished( window._return ? window._return : 1 );
            ";
        }
        $r = WD()->$m($code, $args);
        if (is_object($r) and property_exists($r, 'localizedMessage')) {
            return false;
        }
        return $r;
    }

    public function waitFor($timeout, $sleep, $args, $func, $throw_immediately = false)
    {
        $args = ($args === null ? array() : (is_array($args) ? $args : array($args)));
        array_unshift($args, $this);

        $st = microtime(true);
        $result = false;
        do {
            $last_ex = null;
            try {
                if (($result = call_user_func_array($func, $args)) !== false and $result !== null) {
                    break;
                }
            } catch (Exception $ex) {
                if ($throw_immediately) {
                    throw $ex;
                }
                $last_ex = $ex;
            }
            _usleep($sleep);
        } while (microtime(true) - $st < $timeout);

        if ($last_ex) {
            throw $last_ex;
        }

        return $result;
    }


    function getLoginData()
    {
        $out = false;
        if (($const = $this->BK_ID . "_LOGIN_DATA") and defined($const) and constant($const) != "") {
            $out = constant($const);
        }

        if (isset($_SERVER['_VM_']) and isset($_SERVER['_VM_'][$this->BK_ID])) {
            $out = $_SERVER['_VM_'][$this->BK_ID];
        }

        if (!$out) {
            throw new Exception("ERROR: cant find login data for `{$this->BK_ID}`");
        }

        return constant($this->BK_ID . "_LOGIN_DATA");
    }


    public function _echo($msg, $prepend = true)
    {
        $t = microtime(true);
        $millis = preg_replace("![10]\.!", "", __("%.3f", ($t - intval($t))));

        ($m = $prepend ? __("%s.%s: %s", date("H:i:s"), $millis, $msg) : $msg);
        if (!RMN_IS_MASTER) {
            $m = iconv("utf-8", 'cp866//IGNORE', $m);
        }

        echo $m;

        $this->checkActiveTab();

        return $m;
    }
}
