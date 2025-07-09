<?php
require_once 'functions.php';
require_once __DIR__ . '/../libraries/Psr4AutoloaderClass.php';

$loader = new Psr4AutoloaderClass;
$loader->register();

$loader->addNamespace('NL', __DIR__ . '/classes');

try {
    $PDO = (new NL\PDOFactory())->create([
        'dbhost' => 'localhost',
        'dbname' => 'watch_store',
        'dbuser' => 'root',
        'dbpass' => ''
    ]);
} catch (Exception $ex) {
    echo 'Không thể kết nối đến MySQL, vui lòng kiểm tra lại username/password.<br>';
    exit("<pre>Error: {$ex->getMessage()}</pre>");
}
