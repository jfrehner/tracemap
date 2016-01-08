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


/**
 * Get-request to the root of our app renders the template for the index-page.
 */
$app->get('/', function () use ($app) {
  $app->render('index.php', array(
		'page_title' => "Tracemap"
  ));
});


/**
 * Get-request to /stats renders the template for the stats-page.
 */
$app->get('/stats/', function () use ($app) {
    $app->render('stats.php', array(
        'page_title' => 'Statistics'
    ));
});


/**
 * Get-request to /about renders the template for the about-page.
 */
$app->get('/about/', function () use ($app) {
    $app->render('about.php', array(
        'page_title' => 'About'
    ));
});


/**
 * API CODE
 */
/**
 * Get-request to /api/ping/:url gets the location for a given url.
 *
 * @param {string} $url The url to get the location for.
 * @return {json}       Returns the location-data in a json-object.
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


/**
 * Get-request to /api/:url performs a traceroute-command to the given url, saves
 * the whole output into a file and the DB and returns it as a json-object.
 *
 * @param {string} $url The url to get the traceroute to.
 * @return {json}       Returns id of the traceroute-result.
 */
$app->get('/api/:url', function ($url) {
    $db = new Database();
    $insertID = $db->insertURL($url);

    $cmd = 'traceroute -I '.$url;
    $outputfile = 'traceroutes/'.$insertID.'.txt';
    $pidfile = 'traceroutes/'.$insertID.'.pid';
    exec(sprintf("%s > %s 2>&1 & echo $! >> %s", $cmd, $outputfile, $pidfile));
/*
    // The following code will be obsolete once we read the realtime data from the file
    exec('traceroute -I '.$url.' 2>&1', $out, $code);
    if ($code) {
        die("An error occurred while trying to traceroute: " . join("\n", $out));
    }

    $db->insertTraceroute($insertID, $out);
*/
/*
    $out = array();
    $out['id'] = $insertID;
*/

    $out = array();
    $out['id'] = $insertID;

    echo(json_encode($out));
});


/**
 * Get-request to /api/traceroute/:id gets the traceroute-data for a given id
 * from the DB.
 *
 * @param  {int}  $id   The id to get the location for.
 * @return {json}       Returns the traceroute-data in a json-object.
 */
$app->get('/api/traceroute/:id', function ($id) {
    $db = new Database();
    $result = $db->getTraceroute($id);
    if (count($result) > 0) {
      echo(json_encode($result));
    } else {
      $file = fopen('traceroutes/'.$id.'.pid', 'r');
      $pid = fgets($file);
      fclose($file);

      $out = array();
      $out['data'] = array();

      if (isRunning($pid)) {
        $out['inProgress'] = true;
        $traceFileHandle = fopen('traceroutes/'.$id.'.txt', 'r');

        while(! feof($traceFileHandle))
          {
            array_push($out['data'], fgets($traceFileHandle));
          }

        fclose($traceFileHandle);
      } else {
        $out['inProgress'] = false;
        // $db->insertTraceroute($insertID, $out['data']);
      }
    }

    echo(json_encode($out));
});


/**
 * Get-request to /api/info/basic gets the basic stats for all the executed
 * and saved traceroutes so far.
 *
 * @return {json}       Returns the basic info-data in a json-object.
 */
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


/**
 * Get-request to /api/info/topTraces gets the topTen traced urls for all the executed
 * and saved traceroutes so far.
 *
 * @return {json}       Returns the topTraces info-data in a json-object.
 */
$app->get('/api/info/topTraces', function () {
    $db = new Database();
    $results = $db->getTopTraces();

    echo(json_encode($results));
});

$app->run();
?>
