<?php
error_log(__FILE__ . ':' . __LINE__);
$config = join(DIRECTORY_SEPARATOR, [__DIR__, 'main.ini']);
if (file_exists($config)) {
    $config = parse_ini_file($config, true);
} else {
    error_log('no site file found at ' . $config);
    return;
}
$config = (object) $config;
$config->database = (object) $config->database;
if (isset($config->timezone)) {
    date_default_timezone_set($config->timezone);
}
$dbf = join(DIRECTORY_SEPARATOR, [__DIR__, 'code', 'dbctx.php']);
require_once $dbf;
$dbf = join(DIRECTORY_SEPARATOR, [__DIR__, 'diary.sql']);
$dbctx = Code\DbCtx::getCtx();
$dbctx->upgrade($dbf);
error_log(__FILE__ . ':' . __LINE__);
