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
      } else {
        $out['inProgress'] = false;
        // $db->insertTraceroute($insertID, $out['data']);
      }
    }

    $traceFileHandle = fopen('traceroutes/'.$id.'.txt', 'r');

    while(! feof($traceFileHandle)) {
        $line = fgets($traceFileHandle);
        if (strlen(trim($line)) === 0) {
          continue; // Ignore empty lines
        }

/*

I really hope there is an easier solution for this... I took the following (slightly adjusted) traceroute as an example:

traceroute to facebook.com (69.171.230.68), 64 hops max, 52 byte packets
 1  fritz.box (192.168.1.1)  0.763 ms  0.440 ms  0.358 ms
 2  * * *
 3  217-168-62-93.static.cablecom.ch (217.168.62.93)  15.002 ms  12.998 ms  13.262 ms
 4  ch-otf01b-ra1-ae150-0.aorta.net (84.116.202.225)  23.299 ms  *  17.562 ms
 5  de-fra04a-rc1-et-7-1-2-0.aorta.net (84.116.134.66)  25.825 ms
    de-fra04a-rc1-et-4-0-1-0.aorta.net (84.116.134.230)  16.416 ms  55.887 ms
 6  de-fra03b-ri1-ae25-0.aorta.net (84.116.130.206)  20.179 ms
    de-fra03b-ri1-ae5-0.aorta.net (84.116.133.118)  24.219 ms
    de-fra03b-ri1-ae25-0.aorta.net (84.116.130.206)  16.828 ms
 7  ae11.pr02.fra2.tfbnw.net (103.4.96.196)  17.924 ms  19.900 ms  19.062 ms
 8  be3.bb01.fra2.tfbnw.net (31.13.27.207)  23.355 ms
    be3.bb02.fra2.tfbnw.net (31.13.27.209)  23.556 ms
    be3.bb01.fra2.tfbnw.net (31.13.27.207)  17.970 ms
 9  be11.bb02.cdg1.tfbnw.net (74.119.79.16)  27.042 ms  27.881 ms  25.985 ms
10  be19.bb02.dca1.tfbnw.net (74.119.78.151)  125.157 ms  123.446 ms  117.441 ms
11  be24.bb02.prn2.tfbnw.net (74.119.79.99)  200.466 ms  197.843 ms  197.832 ms
12  ae42.dr08.prn2.tfbnw.net (31.13.31.125)  197.485 ms
    ae42.dr07.prn2.tfbnw.net (31.13.30.217)  199.272 ms
    ae1.dr10.prn1.tfbnw.net (31.13.27.109)  199.012 ms
13  * * *
14  * * *
15  * * *
16  * * *
17  * edge-star-mini-shv-17-prn1.facebook.com (69.171.230.68)  205.513 ms  205.184 ms

The following code should give correct results for this traceroute
*/

        /*
          Use these examples to test
        */
        // $line = "traceroute to facebook.com (69.171.230.68), 64 hops max, 52 byte packets";
        // $line = " 1  fritz.box (192.168.1.1)  0.763 ms  0.763 ms  0.763 ms";
        // $line = " 1  fritz.box (192.168.1.1)  *  *  0.763 ms";
        // $line = "12  ae42.dr08.prn2.tfbnw.net (31.13.31.125)  197.485 ms";
        // $line = "    ae42.dr07.prn2.tfbnw.net (31.13.30.217)  199.272 ms";
        // $line = "13  * * *";
        // $line = "13  * *";
        // $line = "13  *";
        // $line = "17  * edge-star-mini-shv-17-prn1.facebook.com (69.171.230.68)  205.513 ms  205.184 ms";

        $regex = '/(?:\s*)(\S+)/'; // Split whitespaces
        preg_match_all($regex, $line, $matches);
        // print_r($matches);
        // print_r($matches[1]);

        $temp = array();
        $offset = 0; // if there is a * the offset needs to be adjusted
        if (count($matches[1]) > 1 && is_numeric($matches[1][0])) {
          // echo "zeroeth" . $line . "\n";

          // Initialize response parameters
          $temp['hopNr'] = '';
          $temp['host'] = '';
          $temp['ip'] = '';
          $temp['hop1'] = '';
          $temp['hop2'] = '';
          $temp['hop3'] = '';

          $temp['hopNr'] = $matches[1][0]; // Set hop number

          // Check if there is a timeout (still loading)
          if (count($matches[1]) == 2 && $matches[1][1] == '*') {
            $temp['hop1'] = '*';
            $temp['hop2'] = '';
            $temp['hop3'] = '';
            array_push($out['data'], $temp);
            continue;
          }

          // Check if there are two timeout (still loading)
          if (count($matches[1]) == 3 && $matches[1][1] == '*' && $matches[1][2] == '*') {
            $temp['hop1'] = '*';
            $temp['hop2'] = '*';
            $temp['hop3'] = '';
            array_push($out['data'], $temp);
            continue;
          }

          // Check if there are three timeout
          if (count($matches[1]) == 4 && $matches[1][1] == '*' && $matches[1][2] == '*' && $matches[1][3] == '*') {
            $temp['hop1'] = '*';
            $temp['hop2'] = '*';
            $temp['hop3'] = '*';
            array_push($out['data'], $temp);
            continue;
          }

          // Check for timeouts and adjust offset (see Hop 17 above)
          while (count($matches[1]) > 1 + $offset && $matches[1][1 + $offset] === '*') {$offset++;}
          if (count($matches[1]) < 2 + $offset) {
            array_push($out['data'], $temp);
            continue;
          }
          $temp['host'] = $matches[1][1 + $offset];

          // Check for timeouts and adjust offset
          while (count($matches[1]) > 2 + $offset && $matches[1][2 + $offset] === '*') {$offset++;}
          if (count($matches[1]) < 3 + $offset) {
            array_push($out['data'], $temp);
            continue;
          }
          $temp['ip'] = $matches[1][2 + $offset];

          // Check if array is long enough to hold one more hop time
          if (count($matches[1]) < 4 + $offset) {
            array_push($out['data'], $temp);
            continue;
          }
          // Check for timeout
          if ($matches[1][3 + $offset] === '*') {
            $temp['hop1'] = $matches[1][3 + $offset];
            $offset--;
          }

          // Check if ms is present, that means we have a time
          if (count($matches[1]) < 5 + $offset) {
            array_push($out['data'], $temp);
            continue;
          }
          if (count($matches[1]) > 4 + $offset) {
            if ($matches[1][4 + $offset] === 'ms') {
              $temp['hop1'] = $matches[1][3 + $offset];
            }
          }

          // Check if array is long enough to hold one more hop time
          if (count($matches[1]) < 6 + $offset) {
            array_push($out['data'], $temp);
            continue;
          }
          // Check for timeout
          if ($matches[1][5 + $offset] === '*') {
            $temp['hop2'] = $matches[1][5 + $offset];
            $offset--;
          }

          // Check if ms is present, that means we have a time
          if (count($matches[1]) < 7 + $offset) {
            array_push($out['data'], $temp);
            continue;
          }
          if (count($matches[1]) > 6 + $offset) {
            if ($matches[1][6 + $offset] === 'ms') {
              $temp['hop2'] = $matches[1][5 + $offset];
            }
          }

          // Check if array is long enough to hold one more hop time
          if (count($matches[1]) < 8 + $offset) {
            array_push($out['data'], $temp);
            continue;
          }
          // Check for timeout
          if ($matches[1][7 + $offset] === '*') {
            $temp['hop3'] = $matches[1][7 + $offset];
            $offset--;
          }

          // Check if ms is present, that means we have a time
          if (count($matches[1]) < 9 + $offset) {
            array_push($out['data'], $temp);
            continue;
          }
          if (count($matches[1]) > 8 + $offset) {
            if ($matches[1][8 + $offset] === 'ms') {
              $temp['hop3'] = $matches[1][7 + $offset];
            }
          }
          //print_r($temp);
          array_push($out['data'], $temp);
          $offset = 0;
        } else if (substr($line, 0, 4) === "    ") {
          //echo "first" . $line . "\n";


          /*
          *
          *
          *
          *
          * THE CODE BELOW NEEDS TO BE ADJUSTED (SEE CODE ABOVE)
          * not all errors are handled here
          *
          *
          *
          */

          $temp['hopNr'] = '';

          while (count($matches[1]) > 0 + $offset && $matches[1][0 + $offset] === '*') {$offset++;}
          if (count($matches[1]) < 1 + $offset) {
            array_push($out['data'], $temp);
            continue;
          }
          $temp['host'] = $matches[1][0 + $offset];

          while (count($matches[1]) > 1 + $offset && $matches[1][1 + $offset] === '*') {$offset++;}
          if (count($matches[1]) < 2 + $offset) {
            array_push($out['data'], $temp);
            continue;
          }
          $temp['ip'] = $matches[1][1 + $offset];

          if (count($matches[1]) < 3 + $offset) {
            array_push($out['data'], $temp);
            continue;
          }
          if ($matches[1][2 + $offset] === '*') {
            $temp['hop1'] = $matches[1][2 + $offset];
            $offset--;
          }

          if (count($matches[1]) < 4 + $offset) {
            array_push($out['data'], $temp);
            continue;
          }
          if (count($matches[1]) > 3 + $offset) {
            if ($matches[1][3 + $offset] === 'ms') {
              $temp['hop1'] = $matches[1][2 + $offset];
            }
          }

          if (count($matches[1]) < 5 + $offset) {
            array_push($out['data'], $temp);
            continue;
          }
          if ($matches[1][4 + $offset] === '*') {
            $temp['hop2'] = $matches[1][4 + $offset];
            $offset--;
          }

          if (count($matches[1]) < 6 + $offset) {
            array_push($out['data'], $temp);
            continue;
          }
          if (count($matches[1]) > 5 + $offset) {
            if ($matches[1][5 + $offset] === 'ms') {
              $temp['hop2'] = $matches[1][4 + $offset];
            }
          }

          if (count($matches[1]) < 7 + $offset) {
            array_push($out['data'], $temp);
            continue;
          }
          if ($matches[1][6 + $offset] === '*') {
            $temp['hop3'] = $matches[1][6 + $offset];
            $offset--;
          }

          if (count($matches[1]) < 8 + $offset) {
            array_push($out['data'], $temp);
            continue;
          }
          if (count($matches[1]) > 7 + $offset) {
            if ($matches[1][7 + $offset] === 'ms') {
              $temp['hop3'] = $matches[1][6 + $offset];
            }
          }

          array_push($out['data'], $temp);
          $offset = 0;
        } else {
          //echo "second" . $line . "\n";

          $temp['message'] = $line;
          //print_r($temp);
          array_push($out['data'], $temp);
        }
      }

    fclose($traceFileHandle);

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
