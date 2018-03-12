<?php

define('RUN_AS_MYSELF', !defined('RMN_ROOT'));

if (RUN_AS_MYSELF) {
    include(dirname(__FILE__) . "/init.php");
}

if (!defined('WEBDRIVER_BROWSER')) {
 define('WEBDRIVER_BROWSER', 'chrome');
}

require_once RMN_ROOT . "/includes/libs/webdriver/phpwebdriver/WebDriver.php";

function WebDriver__GetSessions()
{
    $x = @json_decode(@file_get_contents(__("http://%s:%s/wd/hub/sessions", WEBDRIVER_HOST, WEBDRIVER_PORT)), true);
    #print_r($x);
    if (!is_array($x) or !isset($x['value']) or !isset($x['value'][0])) {
        return false;
    }
    return $x;
}

function WebDriver__GetCurrentSession()
{
    if(!($x = WebDriver__GetSessions())){
        return false;
    }

    $dir = WebDriver__GetSessionDir($x);

    if (!WebDriver__IsBrowserAlive(basename($dir)) or !$dir) {
        $r = WebDriver__CloseSession($x['value'][0]['id']);
        return false;
    }
    return $x['value'][0]['id'];
}

function WebDriver__CloseSession($s)
{
    return @file_get_contents(__("http://%s:%s/wd/hub/session/%s", WEBDRIVER_HOST, WEBDRIVER_PORT, $s), false,
        stream_context_create(array('http' => array('method' => 'DELETE'))));
}

function WebDriver__CreateSession($clean_profile = false)
{
    $wd = new WebDriver(WEBDRIVER_HOST, WEBDRIVER_PORT, '', WEBDRIVER_BROWSER);

    list($s, $dir) = $wd->connect(WebDriver__GetCapabilities($wd, $clean_profile));
    WebDriver__SaveProfileDir($dir);

    #if(defined('PROXY_FOR_CHROME__RULES_FILE')){
    #    $dir = WebDriver__GetDataDirForSession($s);
    #    WebDriver__ProxyForChrome__ApplyRules($dir, PROXY_FOR_CHROME__RULES_FILE);
    #}

    return $s;
}

function WebDriver__ProxyForChrome__ApplyRules($dir, $rules_file)
{
    $local_storage_file = $dir . '/Default/Local Storage/chrome-extension_iilpibhiihokecnbdkaminemnmecjfed_0.localstorage';

    if (!preg_match("![\/\\\\]!", $rules_file)) {
        if (!preg_match("!ProxyForChrome__!", $rules_file)) {
            $rules_file = 'ProxyForChrome__' . $rules_file;
        }
        $rules_file = RMN_ROOT . '/etc/chrome_extensions/_localStorage/' . $rules_file . '.localstorage';
    }

    var_dump($rules_file);
    var_dump($local_storage_file);

    if (!copy($rules_file, $local_storage_file)) {
        throw new Exception("ERROR: WebDriver__ProxyForChrome__ApplyRules: cant copy rules from `$rules_file`");
    }

    #var_dump($rules_file); exit;

}

function WebDriver__GetDataDirForSession($s)
{
    $x = WebDriver__GetSessions();
    foreach ($x['value'] as $r) {
        if ($r['id'] == $s) {
            return $r['capabilities']['chrome']['userDataDir'];
        }
    }
    throw new Exception("ERROR: WebDriver__GetDataDirForSession: Cant get data_dir for session $s");
}


function WebDriver__IsBrowserAlive($dir)
{
    $t1 = microtime(true);
    exec(__('wmic  process where (commandline LIKE "%%%s%%") get ProcessId,CommandLine', $dir), $v);
    $t2 = microtime(true);
    #print_r($v); echo count($v);
    foreach($v as $s){
        if(stripos($s, "--disable-background-networking") !== false and preg_match("!([0-9]+)$!", $s, $m)){
            #echo "[#] CHROME PID: {$m[1]}\n";
            $_SERVER['CHROME_PID'] = $m[1];
        }
    }
    return count($v) - 4 > 0;
    #echo __("[#] %.3fs\n", $t2-$t1);
}

function WebDriver__IsSeleniumStarted()
{
    $t1 = microtime(true);
    exec(__('wmic  process where (commandline LIKE "%%%s%%") get ProcessId,CommandLine', 'selenium-server.jar'), $v);
    $t2 = microtime(true);
    #print_r($v); echo count($v); exit;
    foreach ($v as $s) {
        if (preg_match("!javaw?\.exe!", $s) and preg_match("!([0-9]+)$!", $s, $m)) {
            #echo "[#] CHROME PID: {$m[1]}\n";
            $_SERVER['SELENIUM_PID'] = $m[1];
        }
    }
    return count($v) - 4 > 0;
    #echo __("[#] %.3fs\n", $t2-$t1);
}

function WebDriver__GetScreenResolution()
{
    exec('wmic desktopmonitor get screenheight, screenwidth', $v);
    if (!preg_match("!^ScreenHeight[ \t]+ScreenWidth!", $v[0]) or !preg_match("!^([0-9]+)[\t ]+([0-9]+)!", $v[1], $m)) {
        die("ERROR: cat detect screen resolution");
    }
    return array(intval($m[2]), intval($m[1]));
}

function WebDriver__CloseAllWindows()
{
    $handles = WD()->getWindowHandles();
    WD()->selectWindow($handles[0]);
    $r = WD()->execute("window.open('about:blank','_blank');");

    $handles = WD()->getWindowHandles();
    for ($i = 0; $i < count($handles) - 1; $i++) {
        WD()->selectWindow($handles[$i]);
        WD()->closeWindow();
    }
}

function isWD()
{
    global $wd;
    return isset($wd);
}

function WebDriver__IsNoWait()
{
    return (isset($_SERVER['WD__NO_WAIT']) and $_SERVER['WD__NO_WAIT']);
}

function WD($closeAll = false, $do_wait = true)
{
    global $wd, $wd_last_alive_ping;
    if (WebDriver__IsNoWait()) {
        $do_wait = false;
    }
    if (!isset($wd)) {
        $just_started = false;

        if (isset($_SERVER['WD__CURRENT_SESSION'])) {
            $s = $_SERVER['WD__CURRENT_SESSION'];
        } else {
            if (WEBDRIVER_BROWSER == 'firefox') {
                WebDriver__ZipProfile(WebDriver__GetProfileDir());
            }

            if (!($s = WebDriver__GetCurrentSession())) {
                $s = WebDriver__CreateSession();
                $just_started = true;
            }
        }
        $_SERVER['WD__CURRENT_SESSION'] = $s;

        $wd = new WebDriver(WEBDRIVER_HOST, WEBDRIVER_PORT, $s, WEBDRIVER_BROWSER);
        if ($just_started) {
            #list($screen_width, $screen_height) = WebDriver__GetScreenResolution(); #var_dump($r); exit;
            #$wd->windowMaximize();
            #$size = $wd->getWindowSize();
            #$wd->setWindowSize($size->width-20, $size->height-205);
            #$wd->setWindowPosition(0, 0);
        }

        if ($do_wait) {
            WebDriver__WaitForIdle($wd);
        }

        #$s = new COM("WScript.Shell");
        #($pid=WebDriver__GetPhpStormPID()) ? $s->AppActivate($pid) : null;

        #$s->AppActivate(isset($_SERVER['CHROME_PID']) ? $_SERVER['CHROME_PID'] : 'chrome');

        #echo __("[#] WD Last Alived: %.2f sec ago\n", microtime(true) - $wd->getLastAlive());
    }

    if ($closeAll) {
        WebDriver__CloseAllWindows();
    }

    if ($do_wait and (!$wd_last_alive_ping or microtime(true) - $wd_last_alive_ping >= 2.5)) {
        list($time, $pid) = $wd->getLastAlive();
        if ($pid > 0 and $pid != getmypid() and isPidAlive($pid)) {
            throw new Exception_SYS_1002($pid);
        }
        $wd->updateLastAlive();
        #$wd->setIsBusy(true);
        $wd_last_alive_ping = microtime(true);
    }


    return $wd;
}

function isPidAlive($pid)
{
    exec(__('tasklist /fi "pid eq %d"', $pid), $v);
    return count($v) > 3;
}

function WebDriver__SetIsBusy($status)
{
    #DB()->query("UPDATE main_actions SET status=%d WHERE name='wd_busy_status'", $status);
    if (!$status) {
        @unlink(WEBDRIVER__LOCK_FILE);
    }
}

function WebDriver__IsBusy()
{
    return DB()->fetchOne("SELECT status FROM main_actions WHERE name='wd_busy_status'");
}

function WebDriver__UpdateLastAlive()
{
    if (!($fp = @fopen(WEBDRIVER__LOCK_FILE, 'x'))) {
        return false;
    }
    fwrite($fp, time() . "|" . getmypid()); #
    fclose($fp);
}

function WebDriver__GetLastAlive()
{
    #return DB()->fetchOne("SELECT UNIX_TIMESTAMP(last_updated_at) FROM main_actions WHERE name='wd_busy_status'");
    if (!preg_match("!^([0-9]+)\|([0-9]+)$!", @file_get_contents(WEBDRIVER__LOCK_FILE), $m)) {
        return array(false, false);
    }
    return array($m[1], $m[2]);
}

define("WEBDRIVER_VM_ID", (strpos(VM_ID, "IK_LOCAL") !== false ? "IK_LOCAL" : VM_ID));

define('WEBDRIVER__DEFAULTS__MAX_WAIT', 180);
define('WEBDRIVER__DEFAULTS__TIMEOUT_FOR_BUSY', 90);
define('WEBDRIVER__LOCK_FILE', __("%s/etc/.tmp/WD__%s.lock", RMN_ROOT, WEBDRIVER_VM_ID));

function WebDriver__WaitForIdle($wd, $max_wait = WEBDRIVER__DEFAULTS__MAX_WAIT, $timeout_for_busy = WEBDRIVER__DEFAULTS__TIMEOUT_FOR_BUSY)
{
    $st = microtime(true);
    $is_idle = false;
    $do_wait = false;

    do {
        #DB()->query("LOCK TABLES main_actions WRITE");
        #echo __("[#] %.3fs [%s]\n", microtime(true) - floatval($wd->getLastAlive()), ($wd->isBusy() ? 'busy' : 'idle'));

        list($time, $pid) = $wd->getLastAlive(); #
        if ($pid > 0 and ($pid == getmypid() or !isPidAlive($pid))) {
            if ($pid != getmypid()) {
                $wd->setIsBusy(false);
                $wd->updateLastAlive();
            }
            $is_idle = true;
            break;
        }
        if (!($time) or microtime(true) - $time > WEBDRIVER__DEFAULTS__TIMEOUT_FOR_BUSY) {
            echo __("[~] %d\t%d\n", $time, microtime(true) - $time);
            $wd->setIsBusy(false);
            $wd->updateLastAlive();
            continue;
        }

        if (!$do_wait) {
            echo "[#] Waiting when WD is idle... ";
            $do_wait = true;
        }
        _usleep(0.25);
    } while (microtime(true) - $st < $max_wait);


    if (!$is_idle) {
        throw new Exception_SYS_1001("$max_wait sec");
    }

    if ($do_wait) {
        echo __("OK [%.2fs]\n", microtime(true) - $st);
    }

    return $is_idle;
}

function WebDriver__GetPhpStormPID()
{
    exec('tasklist /FI "IMAGENAME eq phpstorm.exe"', $r);
    foreach ($r as $s) {
        if (preg_match("!^PhpStorm\.exe[\t ]+([0-9]+)[\t ]+!", $s, $m)) {
            return $m[1];
        }
    }
    return false;
}

function WebDriver__GetSessionDir($x)
{
    $dir = false;
    switch (WEBDRIVER_BROWSER) {
        case 'chrome':
            if (isset($x['value'][0]['capabilities']['chrome'])) {
                $dir = $x['value'][0]['capabilities']['chrome']['userDataDir'];
            }
            break;
        case 'firefox':
        case 'antidetect':
            if (isset($x['value'][0]['capabilities']['moz:profile'])) {
                $dir = $x['value'][0]['capabilities']['moz:profile'];
            }
            break;
    }

    return $dir;
}

function WebDriver__SaveProfileDir($dir)
{
    file_put_contents(__("%s/etc/.tmp/%s-profile.txt", RMN_ROOT, WEBDRIVER_BROWSER), $dir);
}

function WebDriver__GetProfileDir()
{
    return @file_get_contents(__("%s/etc/.tmp/%s-profile.txt", RMN_ROOT, WEBDRIVER_BROWSER));
}

function WebDriver__GetProfileArchive()
{
    return __("c:/selenium/%s-profile.zip", WEBDRIVER_BROWSER);
}

function WebDriver__GetEncodedProfile() {
    $profile = @file_get_contents(WebDriver__GetProfileArchive());
    if ($profile) {
        return base64_encode($profile);
    }
}

function WebDriver__ZipProfile($dir = '')
{
    if ($dir and is_dir($dir)) {
        $zipFile = WebDriver__GetProfileArchive();
        $zip = new DirZipArchive;
        $zip->open($zipFile, DirZipArchive::CREATE | DirZipArchive::OVERWRITE);
        $zip->addDir($dir);
        for($i = 0; $i < $zip->numFiles; $i++) {
            $entry_info = $zip->statIndex($i);
            if (substr($entry_info["name"], 0, strlen('cache2')) == 'cache2') {
                $zip->deleteIndex($i);
            }
        }
        $zip->close();
    }
}

function WebDriver__GetCapabilities($wd, $clean_profile)
{
    if (WEBDRIVER_BROWSER == 'firefox') {
        $options = array();

        if ($profile = WebDriver__GetEncodedProfile()) {
            $options['profile'] = $profile;
        }

        $capabilities = array(
            'capabilities' => array(
                'alwaysMatch' => array(
                    'browserName'         => 'firefox',
                    'acceptInsecureCerts' => true,
                    'moz:firefoxOptions'  => $options
                )
            )
        );
    }

    if (WEBDRIVER_BROWSER == 'chrome') {
        $extensions = array();
        if (defined('ENABLE_WEBRTC_BLOCK') and ENABLE_WEBRTC_BLOCK) {
            $extensions[] = 'Z:/home/test1.ru/betbot/etc/chrome_extensions/EasyWebRTCBlock/';
        }
        if (defined('ENABLE_PROXY_FOR_CHROME') and ENABLE_PROXY_FOR_CHROME) {
            $extensions[] = 'Z:/home/test1.ru/betbot/etc/chrome_extensions/ProxyForChrome/';
        }

        if (defined('ENABLE_ADBLOCK_FOR_CHROME') and ENABLE_ADBLOCK_FOR_CHROME) {
            $extensions[] = 'Z:/home/test1.ru/betbot/etc/chrome_extensions/Adblock/';
        }

        $capabilities = array(
            'desiredCapabilities' => array(
                'browserName'   => 'chrome',
                'Proxy'         => json_encode(array('proxyType' => 'system')),
                'chromeOptions' => array(
                    'args' => array(
                        'disable-web-security',
                        'start-maximized',
                        ($clean_profile ? 'start-maximized' : 'user-data-dir=c:/selenium/chrome-profile'),
                        (count($extensions) > 0) ? 'load-extension=' . implode(",", $extensions) : 'start-maximized'
                    )
                )
            )
        );
    }

    return $capabilities;
}


if (!RUN_AS_MYSELF) {
    return;
}

$t1 = microtime(true);
$x = WD(true)->getWindowHandles();
$t2 = microtime(true);
print_r($x);
