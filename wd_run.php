<?php
include(__DIR__ . "/init.php");

if (!($r = WebDriver__IsSeleniumStarted())) {
    echo "[+] Starting Selenium... ";

    $cmd = __('c:/selenium/Start_Selenium.bat ' . WEBDRIVER_BROWSER);

    $WshShell = new COM("WScript.Shell");
    $WshShell->Run($cmd, 1, false);

    sleep(4);

    try {
        if (!($r = WebDriver__IsSeleniumStarted())) {
            throw new Exception("Something is going wrong (unknown error)");
        }
        echo __("OK (PID=%d)\n", $_SERVER['SELENIUM_PID']);
    } catch (Exception $ex) {
        echo "ERROR: {$ex->getMessage}\n";
        die();
    }

} else {
    echo __("[#] Selenium was already started (PID=%d)\n", $_SERVER['SELENIUM_PID']);
}
