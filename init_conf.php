<?php

define("RMN_ROOT", dirname(__FILE__));

define("VM_ID", "WD_SAMPLE");

define('WEBDRIVER_HOST', isset($_VM_['WD_HOST']) ? $_VM_['WD_HOST'] : 'localhost');
define('WEBDRIVER_PORT', 4444);

define('MYSQLDB_LOGSDIR', RMN_ROOT . "/etc/.logs/");
define('TMP_DIR', RMN_ROOT . "/etc/.tmp/");

return 1;
