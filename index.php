<?php

// Can be commented out in production
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define( 'TMPL', './templates/');

require 'vendor/autoload.php';
include 'lib/mysql.php';
include_once 'lib/functions.php';

// Initialize Slim
$app = new \Slim\Slim();


/**
 * Get-request to the root of our app renders the template for the index-page.
 */
$app->get('/(:site)', function () use ($app) {
  $app->render('index.html');
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

    $result = $db->getCachedURL($url);

    if (count($result) === 0) {
      // $out = file_get_contents('http://www.freegeoip.net/json/' . $url);
      $out = file_get_contents('http://ip-api.com/json/' . $url);

      $db->insertIPLocation($url, $out);
      $result = $db->getCachedURL($url);
    }

    echo(json_encode($result));
});


/**
 * Post-request to /api/traceroute/ performs a traceroute-command to the given url.
 *
 * The traceroute command output will be saved to a file.
 *
 * @return {int}  Returns id of the traceroute request.
 */
$app->post('/api/traceroute/', function () use ($app) {
    $url = $app->request->post('url');

    $db = new Database();
    $insertID = $db->insertURL($url);

    // Define parameters and execute traceroute command
    $cmd = 'traceroute -I '.$url;
    $outputfile = 'traceroutes/'.$insertID.'.txt';
    $pidfile = 'traceroutes/'.$insertID.'.pid';
    exec(sprintf("%s > %s 2>&1 & echo $! >> %s", $cmd, $outputfile, $pidfile));

    $out = array();
    $out['id'] = $insertID;

    echo(json_encode($out));
});


/**
 * Get-request to /api/traceroute/:id gets the traceroute-data for a given id
 * from the file or DB.
 *
 * @param  {int}  $id   The id to get the location for.
 * @return {json}       Returns the traceroute-data in a json-object.
 */
$app->get('/api/traceroute/:id', function ($id) {
    // Add id to the response
    $out = array();
    $out['id'] = $id;

    $db = new Database();

    // Check if traceroute is marked as finished in the DB. If that's the case,
    // simply return the data and set in progress to false
    if ($db->tracerouteFinished($id)) {
      $result = $db->getTraceroute($id);
      $out['data'] = $result;
      $out['inProgress'] = false;
      echo(json_encode($out));
      exit;
    } else {
      // If the traceroute command is not marked as finished, read the PID from
      // the file and check if it is still running
      $file = fopen('traceroutes/'.$id.'.pid', 'r');
      $pid = fgets($file);
      fclose($file);

      $out['data'] = array();

      if (isRunning($pid)) {
        $out['inProgress'] = true;
      } else {
        $out['inProgress'] = false;
      }
    }

    // At this point we know we need to get the latest data from the output file
    $traceFileHandle = fopen('traceroutes/'.$id.'.txt', 'r');

    while(! feof($traceFileHandle)) {
        $line = fgets($traceFileHandle);
        if (strlen(trim($line)) === 0) {
          continue; // Ignore empty lines
        }

        /**
         * This part of the code parses one line of the traceroute output.
         *
         * There is probably a way easier solution to it. (Maybe using regex?)
         * The main reason why the following segment is so long is because we always
         * need to make sure the index we want to access exists. So there are many
         * checks for that. Especially the last part could be modularized into a function
         * that gets called with a different offset each time.
         *
         * This code should be put into a seperate function as soon as unit tests are
         * working so it can be tested for any errors.
         */

        /*
        A real example of a traceroute to facebook.com

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
        */

        // Some examples to test if the parser is working
        //
        // $line = "traceroute to facebook.com (69.171.230.68), 64 hops max, 52 byte packets";
        // $line = " 1  fritz.box (192.168.1.1)  0.763 ms  0.763 ms  0.763 ms";
        // $line = " 2  fritz.box (192.168.1.1)  *  *  0.763 ms";
        // $line = "10  ae42.dr08.prn2.tfbnw.net (31.13.31.125)  197.485 ms";
        // $line = "    ae42.dr07.prn2.tfbnw.net (31.13.30.217)  199.272 ms";
        // $line = "13  *";
        // $line = "13  * *";
        // $line = "13  * * *";
        // $line = "15   (72.8.162.46)  266.393 ms  224.981 ms  222.625 ms";
        // $line = "17  * edge-star-mini-shv-17-prn1.facebook.com (69.171.230.68)  205.513 ms  205.184 ms";

        $regex = '/(?:\s*)(\S+)/'; // Split whitespaces
        preg_match_all($regex, $line, $matches);

        // Initialize response parameters
        $temp = array();
        $temp['hopNumber'] = '';
        $temp['hostname'] = '';
        $temp['ip'] = '';
        $temp['rtt1'] = '';
        $temp['rtt2'] = '';
        $temp['rtt3'] = '';

        $offset = 0; // if there is a * the array index offset needs to be adjusted

        if (count($matches[1]) > 1 && is_numeric($matches[1][0])) {
          // check if line has more than 1 element and the first one is numeric (hop nr)

          $temp['hopNumber'] = $matches[1][0]; // Set hop number

          // Check if there is exactly one timeout (still loading)
          if (count($matches[1]) == 2 && $matches[1][1] == '*') {
            $temp['rtt1'] = '-1';
            $temp['rtt2'] = '';
            $temp['rtt3'] = '';
            array_push($out['data'], $temp);
            continue;
          }

          // Check if there are exactly two timeouts (still loading)
          if (count($matches[1]) == 3 && $matches[1][1] == '*' && $matches[1][2] == '*') {
            $temp['rtt1'] = '-1';
            $temp['rtt2'] = '-1';
            $temp['rtt3'] = '';
            array_push($out['data'], $temp);
            continue;
          }

          // Check if there are exactly three timeouts
          if (count($matches[1]) == 4 && $matches[1][1] == '*' && $matches[1][2] == '*' && $matches[1][3] == '*') {
            $temp['rtt1'] = '-1';
            $temp['rtt2'] = '-1';
            $temp['rtt3'] = '-1';
            array_push($out['data'], $temp);
            continue;
          }

          // Check for timeouts and adjust offset until value is no longer *
          // Sometimes lines start with a * after the hop number (see Hop 17 in example)
          while (count($matches[1]) > 1 + $offset && $matches[1][1 + $offset] === '*') { $offset++; }
          // Check if there is more coming, otherwise this line is finished
          if (count($matches[1]) < 2 + $offset) {
            array_push($out['data'], $temp);
            continue;
          }

          // Check if the current element is the hostname or IP
          if (substr($matches[1][1 + $offset], 0, 1) == '(') {
            $offset--;
          } else {
            $temp['hostname'] = $matches[1][1 + $offset];
          }

          // Check for timeouts and adjust offset until value is no longer *
          while (count($matches[1]) > 2 + $offset && $matches[1][2 + $offset] === '*') { $offset++; }
          // Check if there is more coming, otherwise this line is finished
          if (count($matches[1]) < 3 + $offset) {
            array_push($out['data'], $temp);
            continue;
          }
          $temp['ip'] = substr($matches[1][2 + $offset], 1, -1); // Remove '(' and ')'

          // Check if array is long enough to hold one more hop time, otherwise line is finished
          if (count($matches[1]) < 4 + $offset) {
            array_push($out['data'], $temp);
            continue;
          }
          // Check for timeout and adjust offset if necessary
          if ($matches[1][3 + $offset] === '*') {
            $temp['rtt1'] = $matches[1][3 + $offset];
            $offset--;
          }

          // Check if array is long enough to hold one more hop time, otherwise line is finished
          if (count($matches[1]) < 5 + $offset) {
            array_push($out['data'], $temp);
            continue;
          }
          // Check if ms is present, that means we have a time
          if (count($matches[1]) > 4 + $offset) {
            if ($matches[1][4 + $offset] === 'ms') {
              $temp['rtt1'] = $matches[1][3 + $offset];
            }
          }

          // Check if array is long enough to hold one more hop time, otherwise line is finished
          if (count($matches[1]) < 6 + $offset) {
            array_push($out['data'], $temp);
            continue;
          }
          // Check for timeout and adjust offset if necessary
          if ($matches[1][5 + $offset] === '*') {
            $temp['rtt2'] = $matches[1][5 + $offset];
            $offset--;
          }

          // Check if array is long enough to hold one more hop time, otherwise line is finished
          if (count($matches[1]) < 7 + $offset) {
            array_push($out['data'], $temp);
            continue;
          }
          // Check if ms is present, that means we have a time
          if (count($matches[1]) > 6 + $offset) {
            if ($matches[1][6 + $offset] === 'ms') {
              $temp['rtt2'] = $matches[1][5 + $offset];
            }
          }

          // Check if array is long enough to hold one more hop time, otherwise line is finished
          if (count($matches[1]) < 8 + $offset) {
            array_push($out['data'], $temp);
            continue;
          }
          // Check for timeout and adjust offset if necessary
          if ($matches[1][7 + $offset] === '*') {
            $temp['rtt3'] = $matches[1][7 + $offset];
            $offset--;
          }

          // Check if array is long enough to hold one more hop time, otherwise line is finished
          if (count($matches[1]) < 9 + $offset) {
            array_push($out['data'], $temp);
            continue;
          }
          // Check if ms is present, that means we have a time
          if (count($matches[1]) > 8 + $offset) {
            if ($matches[1][8 + $offset] === 'ms') {
              $temp['rtt3'] = $matches[1][7 + $offset];
            }
          }

          array_push($out['data'], $temp);
          $offset = 0; // reset offset
        } else if (substr($line, 0, 4) === "    ") {
          // Check if the line starts with 4 empty spaces

          // Check for timeouts and adjust offset until value is no longer *
          while (count($matches[1]) > 0 + $offset && $matches[1][0 + $offset] === '*') { $offset++; }
          if (count($matches[1]) < 1 + $offset) {
            array_push($out['data'], $temp);
            continue;
          }
          $temp['hostname'] = $matches[1][0 + $offset];

          // Check for timeouts and adjust offset until value is no longer *
          while (count($matches[1]) > 1 + $offset && $matches[1][1 + $offset] === '*') { $offset++; }
          if (count($matches[1]) < 2 + $offset) {
            array_push($out['data'], $temp);
            continue;
          }
          $temp['ip'] = substr($matches[1][1 + $offset], 1, -1);

          // Check if array is long enough to hold one more hop time, otherwise line is finished
          if (count($matches[1]) < 3 + $offset) {
            array_push($out['data'], $temp);
            continue;
          }
          // Check for timeout and adjust offset if necessary
          if ($matches[1][2 + $offset] === '*') {
            $temp['rtt1'] = $matches[1][2 + $offset];
            $offset--;
          }

          // Check if array is long enough to hold one more hop time, otherwise line is finished
          if (count($matches[1]) < 4 + $offset) {
            array_push($out['data'], $temp);
            continue;
          }
          // Check if ms is present, that means we have a time
          if (count($matches[1]) > 3 + $offset) {
            if ($matches[1][3 + $offset] === 'ms') {
              $temp['rtt1'] = $matches[1][2 + $offset];
            }
          }

          // Check if array is long enough to hold one more hop time, otherwise line is finished
          if (count($matches[1]) < 5 + $offset) {
            array_push($out['data'], $temp);
            continue;
          }
          // Check for timeout and adjust offset if necessary
          if ($matches[1][4 + $offset] === '*') {
            $temp['rtt2'] = $matches[1][4 + $offset];
            $offset--;
          }

          // Check if array is long enough to hold one more hop time, otherwise line is finished
          if (count($matches[1]) < 6 + $offset) {
            array_push($out['data'], $temp);
            continue;
          }
          // Check if ms is present, that means we have a time
          if (count($matches[1]) > 5 + $offset) {
            if ($matches[1][5 + $offset] === 'ms') {
              $temp['rtt2'] = $matches[1][4 + $offset];
            }
          }

          // Check if array is long enough to hold one more hop time, otherwise line is finished
          if (count($matches[1]) < 7 + $offset) {
            array_push($out['data'], $temp);
            continue;
          }
          // Check for timeout and adjust offset if necessary
          if ($matches[1][6 + $offset] === '*') {
            $temp['rtt3'] = $matches[1][6 + $offset];
            $offset--;
          }

          // Check if array is long enough to hold one more hop time, otherwise line is finished
          if (count($matches[1]) < 8 + $offset) {
            array_push($out['data'], $temp);
            continue;
          }
          // Check if ms is present, that means we have a time
          if (count($matches[1]) > 7 + $offset) {
            if ($matches[1][7 + $offset] === 'ms') {
              $temp['rtt3'] = $matches[1][6 + $offset];
            }
          }

          array_push($out['data'], $temp);
          $offset = 0;
        } else {
          // Lines that don't match anywhere above are most likely status messages
          // and don't need to be parsed.

          $temp['message'] = $line;

          array_push($out['data'], $temp);
        }
      }

    fclose($traceFileHandle);

    if (!$out['inProgress']) {
      // If the traceroute command is not running anymore, we can update the db
      // and delete the files
      $db->updateTracerouteFinished($id);
      unlink('traceroutes/'.$id.'.pid');
      unlink('traceroutes/'.$id.'.txt');
    }

    // Insert all the parsed information into the db
    foreach ($out['data'] as $key => $value) {
      $db->insertTraceroute($id, $value);
    }

    // Read the data we just inserted and return it to the client
    $result = $db->getTraceroute($id);
    $out['data'] = $result;

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
    $time = round(($timesResults[0]['AVG1'] + $timesResults[0]['AVG2'] + $timesResults[0]['AVG3']) / 3, 3);

    $out = array();
    $out['numTraces'] = $db->getNumberOfTraces()[0]['COUNT(*)'];
    $out['numHops'] = $db->getNumberOfHops()[0]['COUNT(*)'];
    $out['hopTime'] = $time;
    $out['averageHopsPerRoute'] = $db->getAverageHopsPerRoute()[0]['averageHopCount'];

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


/**
 * Get-request to get all hop times that are stored in the DB.
 */
$app->get('/api/info/hoptimes', function() {
  $db = new Database();
  $results = $db->getAllHopTimes();

  echo(json_encode($results));
});


/**
 * Get-request to get a list of contries we had hops in, as well as a count of how many times we had hops in that country.
 */
$app->get('/api/info/countryCount', function() {
  $db = new Database();
  $results = $db->getCountryCount();

  echo(json_encode($results));
});


/**
 * Get-request to get all hop times of the current request.
 */
$app->get('/api/info/hoptime/:id', function($id) {
  $db = new Database();
  $results = $db->getHopTimesOfID($id);

  echo(json_encode($results));
});


$app->run();
