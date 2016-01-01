<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define( 'TMPL', './templates/');

require 'vendor/autoload.php';
include 'lib/mysql.php';

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

    //TODO: Add cache

    //$out = file_get_contents('http://www.freegeoip.net/json/' . $url);
    $out = file_get_contents('http://ip-api.com/json/' . $url);

    $db = new Database();
    $db->insertIPLocation($url, $out);

    echo($out);
});

$app->get('/api/:url', function ($url) {
    $db = new Database();
    $insertID = $db->insertURL($url);

    exec('traceroute -I '.$url.' 2>&1', $out, $code);
    if ($code) {
        die("An error occurred while trying to traceroute: " . join("\n", $out));
    }

    $db->insertTraceroute($insertID, $out);

    echo(json_encode($out));
});

$app->get('/api/info/basic', function () {
    $db = new Database();
    $timesResults = $db->getAverageHopTime();
    $time = ($timesResults[0]['AVG1'] + $timesResults[0]['AVG2'] + $timesResults[0]['AVG3']) / 3;

    $out = array();
    $out['numTraces'] = $db->getNumberOfTraces()[0]['COUNT(*)'];
    $out['numHops'] = $db->getNumberOfHops()[0]['COUNT(*)'];
    $out['hopTime'] = $time;

    echo(json_encode($out));
});

$app->get('/api/info/topTraces', function () {
    $db = new Database();
    $results = $db->getTopTraces();

    echo(json_encode($results));
});

$app->run();
?>
