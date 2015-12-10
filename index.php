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

$app->get('/tracemap/:url', function ($url) {
    echo "Looking up $url";
    exec('traceroute '.$url.' 2>&1', $out, $code);
    if ($code) {
        die("An error occurred while trying to traceroute: " . join("\n", $out));
    }
    print_r($out);
});

$app->get('/stats/', function () use ($app) {
    $app->render('stats.php', array(
        'page_title' => 'Statistics'
    ));
});

$app->get('/about', function () {
    $app->render('about.php', array(
        'page_title' => 'About'
    ));
});
$app->run();
?>
