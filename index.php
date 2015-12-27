<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define( 'TMPL', './templates/');

require 'vendor/autoload.php';
//include 'lib/mysql.php';

//$db = new Database();

$app = new \Slim\Slim();

$app->get('/', function () use ($app) {
  $app->render('index.php', array(
		'page_title' => "Tracemap"
  ));
});

$app->get('/stats/', function () use ($app) {
    $app->render('stats.php', array(
        'page_title' => 'Statistics'
    ));
});

$app->get('/about/', function () use ($app) {
    $app->render('about.php', array(
        'page_title' => 'About'
    ));
});

/**
 * API CODE
 */
$app->get('/api/ping/:url', function ($url) {

    //$out = file_get_contents('http://www.freegeoip.net/json/' . $url);
    $out = file_get_contents('http://ip-api.com/json/' . $url);

    echo($out);
});

$app->get('/api/:url', function ($url) {

    exec('traceroute -I '.$url.' 2>&1', $out, $code);
    if ($code) {
        die("An error occurred while trying to traceroute: " . join("\n", $out));
    }

    echo(json_encode($out));
});

$app->run();
?>
