<?php
if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $file = __DIR__ . $_SERVER['REQUEST_URI'];
    if (is_file($file)) {
        return false;
    }
}

require __DIR__ . '/../vendor/autoload.php';

session_start();

// Instantiate the app
$app_settings = require __DIR__ . '/../src/settings.php';
$app = new \Slim\App($app_settings);

$container = $app->getContainer();
$settings = $container->get('settings');

//~ $db = new PDO('sqlite:/usr/share/nginx/html/solmas-masjid/masjid.db');
$db = new PDO('sqlite:../masjid.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sth = $db->query('SELECT * FROM setting WHERE id="1";');
$row = $sth->fetchAll(PDO::FETCH_CLASS);
$masjid_setting = $row[0];

$api_token = $masjid_setting->api_token;



////////////////////////////////////////////////////////////////////////////////
//
// Set up dependencies
//
////////////////////////////////////////////////////////////////////////////////

require __DIR__ . '/../src/dependencies.php';

// Register middleware
require __DIR__ . '/../src/middleware.php';

// Register routes
require __DIR__ . '/../src/routes.php';


////////////////////////////////////////////////////////////////////////////////
//
// Run app
//
////////////////////////////////////////////////////////////////////////////////
$app->run();
