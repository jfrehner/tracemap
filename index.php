<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define( 'TMPL', './templates/');

require 'vendor/autoload.php';
include 'lib/mysql.php';

function isRunning($pid){
  try {
      $result = shell_exec(sprintf("ps %d", $pid));
      if( count(preg_split("/\n/", $result)) > 2){
          return true;
      }
  } catch (Exception $e) {}

  return false;
}

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

    $db = new Database();

    $result = $db->getCachedURL($url); //TODO: Hostname can have multiple ips

    if (count($result) === 0) {
      //$out = file_get_contents('http://www.freegeoip.net/json/' . $url);
      $out = file_get_contents('http://ip-api.com/json/' . $url);

      $db->insertIPLocation($url, $out);
      $result = $db->getCachedURL($url);
    }

    echo(json_encode($result));
});

$app->get('/api/:url', function ($url) {
    $db = new Database();
    $insertID = $db->insertURL($url);

    $cmd = 'traceroute -I '.$url;
    $outputfile = 'traceroutes/'.$insertID.'.txt';
    $pidfile = 'traceroutes/'.$insertID.'.pid';
    exec(sprintf("%s > %s 2>&1 & echo $! >> %s", $cmd, $outputfile, $pidfile));

    // The following code will be obsolete once we read the realtime data from the file
    exec('traceroute -I '.$url.' 2>&1', $out, $code);
    if ($code) {
        die("An error occurred while trying to traceroute: " . join("\n", $out));
    }

    $db->insertTraceroute($insertID, $out);

/*
    $out = array();
    $out['id'] = $insertID;
*/
    echo(json_encode($out));
});

$app->get('/api/traceroute/:id', function ($id) {
    $db = new Database();
    $result = $db->getTraceroute($id);
    if (count($result)) {
      echo(json_encode($result));
    } else {
      $file = fopen('traceroutes/'.$id.'.txt', 'r');
      $pid = fgets($file);
      fclose($file);

      if (isRunning($pid)) {
        $status = 'inProgress';
      } else {
        $status = 'finished';
        //$db->insertTraceroute($insertID, $out);
      }
    }


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
