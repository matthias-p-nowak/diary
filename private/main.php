<?php

$config = join(DIRECTORY_SEPARATOR, [__DIR__, 'main.ini']);
if (file_exists($config)) {
    $config = parse_ini_file($config, true);
} else {
    error_log('no site file found at ' . $config);
    $config = [];
}

$config = (object) $config;
$config->database = (object) $config->database;

if (isset($config->timezone)) {
    date_default_timezone_set($config->timezone);
}

// setting auto loader to this folder
$oldPath = get_include_path();
$newPath = join(PATH_SEPARATOR, [$oldPath, __DIR__]);
set_include_path($newPath);
foreach (spl_autoload_functions() as $f) {
    spl_autoload_unregister($f);
}
spl_autoload_extensions('.php');
spl_autoload_register();

session_start();

function ShowHome(){
    $p=new Code\Page();
    $p->showHome();
}

$res = $_SERVER['PATH_INFO'] ?? '/home';
try {
    if (!isset($_SESSION['authenticated'])) {
        $login = new Code\Login();
        if (!$login->login()) {
            return;
        }
        $_SESSION['authenticated'] = true;
        error_log(__FILE__ . ':' . __LINE__ . ' ' . __FUNCTION__ . ' login was successful');
    }
    $status = ($_SESSION['status'] ??= new stdClass());
    match ($res) {
        default => ShowHome(),
    };
} catch (Exception $ex) {
    error_log("got exception $ex");
} finally {
    // logging statistics
    $time = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
    $time = number_format($time, 4);
    $included = \get_included_files();
    $incCnt = \count($included);
    $files = \print_r($included, true);
    error_log("used  $time seconds and $incCnt files: $files");
}
