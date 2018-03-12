<?php

function __($msg)
{
    $args = func_get_args();
    if (count($args) > 1) {
        $args = array_slice($args, 1);
    } else {
        $args = array();
    }
    return vsprintf($msg, $args);
}

function include_dir($dir, $postfix = "class")
{
    if (!is_dir($dir)) {
        return;
    }
    $files = glob("$dir/*$postfix*.php");
    foreach ($files as $file) {
        include_once $file;
    }
}

function _usleep($sec)
{
    $t1 = microtime(true);
    do {
        usleep(10);
    } while (microtime(true) - $t1 < $sec);
}

function getConstantOrServer($var_name){
    if(defined($var_name)){
        return constant($var_name);
    }
    if(isset($_SERVER[$var_name])){
        return $_SERVER[$var_name];
    }
    return null;
}

function getAliveProcess($pat1, $pat2, $debug=false){
    $t1=microtime();
    exec(__('wmic  process where (commandline LIKE "%%%s%%") get ProcessId,CommandLine', $pat1), $v);
    $t2=microtime();

    array_shift($v);
    if($debug){
        echo __("%.4f\n\n", $t2-$t1);
        print_r($v);
    }

    $pids = array();

    foreach($v as $line){
        if($debug){
            echo "$line\n";
        }
        if(strpos($line, $pat2) !== false){
            if(!preg_match("![ ]+([0-9]+)$!", $line, $m)){
                die("ERROR: cant extract PID from `$line`");
            }
            $pids[] = $m[1];
        }
    }
    return $pids;
}

function killProcess($pid){
    if(!is_array($pid)){
        $pid = array($pid);
    }
    foreach($pid as $p){
        if(preg_match("!^[0-9]+$!", $p)){
            exec("taskkill /pid $p /f");
        }
    }
}
