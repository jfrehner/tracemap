<?php

// TODO: Move to a better location
function getUserIP()
{
    $client  = @$_SERVER['HTTP_CLIENT_IP'];
    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
    $remote  = $_SERVER['REMOTE_ADDR'];

    if(filter_var($client, FILTER_VALIDATE_IP)) {
        $ip = $client;
    } elseif(filter_var($forward, FILTER_VALIDATE_IP)) {
        $ip = $forward;
    } else {
        $ip = $remote;
    }

    return $ip;
}


/**
 * Class to handle all DB-related requests.
 */
class Database {


  /**
   * The DB-Object.
   * @var Object  Mysqli-Object
   */
  private $db = null;


  /**
   * Constructor of the Database.
   * Creates a connection to the DB.
   */
  function Database() {
    $this->connect();
  }


  /**
   * Connects to the DB through the creation of a new mysqli-object.
   * All the parameters necessary to create a mysqli-Object are initialized
   * in this method, such as the user-name, password, server and database-name.
   */
  private function connect() {
    require 'config.php';
  	$this->db = new mysqli($server, $user, $pass, $database);
  }


  /**
   * Inserts the passed URL into the DB (table 'search') together with the
   * IP of the user.
   *
   * @param  String   $url    The URL to insert into the DB.
   * @return Int              The insert_id returned by the DB after an Insert.
   */
  public function insertURL($url) {
    $this->db->query('INSERT INTO search (url, requesterIP) VALUES ("'.$url.'", "'.getUserIP().'")');
    return $this->db->insert_id;
  }


  /**
   * Inserts a traceroute with all its hops into the DB.
   *
   * @param  Int     $searchID The id of the search the traceroute belongs to.
   * @param  array   $out      The array containing all the hops as strings.
   */
  public function insertTraceroute($searchID, $data) {
    if (array_key_exists('message', $data)) {
      $this->db->query('INSERT INTO hops (searchID, message)
      VALUES ("'.$searchID.'", "'.$data['message'].'")');
    } else {
      $this->db->query('INSERT INTO hops (searchID, hopNumber, hostname, ip, rtt1, rtt2, rtt3)
      VALUES ("'.$searchID.'", "'.$data['hopNumber'].'", "'.$data['hostname'].'", "'.$data['ip'].'", "'.$data['rtt1'].'", "'.$data['rtt2'].'", "'.$data['rtt3'].'")
      ON DUPLICATE KEY UPDATE hopNumber = "'.$data['hopNumber'].'", hostname = "'.$data['hostname'].'", ip = "'.$data['ip'].'", rtt1= "'.$data['rtt1'].'", rtt2 = "'.$data['rtt2'].'", rtt3 = "'.$data['rtt3'].'"');
      $this->requestIPLocation($data['hostname'], $data['ip']);
    }
  }

  public function insertIPLocation($hostname, $ip, $out) {
    $result = json_decode($out, true);

    if($result['status'] !== 'fail') {
      $result = $this->db->query('INSERT INTO ip_locations (ip, hostname, asn, city, country, countryCode, isp, org, region, regionName, timezone, zip, longitude, latitude,status)
      VALUES ("'.$result['query'].'", "'.$hostname.'", "'.$result['as'].'", "'.$result['city'].'", "'.$result['country'].'", "'.$result['countryCode'].'", "'.$result['isp'].'", "'.$result['org'].'",
      "'.$result['region'].'", "'.$result['regionName'].'", "'.$result['timezone'].'", "'.$result['zip'].'", "'.$result['lon'].'", "'.$result['lat'].'", 1)');
    } else {
      $result = $this->db->query('INSERT INTO ip_locations (ip, hostname, status)
      VALUES ("'.$ip.'", "'.$hostname.'", 0)');
    }
  }

  public function getCachedURL($url) {
    $data = [];
    $result = $this->db->query("SELECT * FROM ip_locations WHERE hostname LIKE '".$url."'");
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
      $data[] = $row;
    }
    if (count($data) > 0) {
      return $data[0];
    } else {
      return [];
    }
  }

  public function getNumberOfTraces() {
    $data = [];
    $result = $this->db->query("SELECT COUNT(*) FROM search");
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
      $data[] = $row;
    }
    return $data;
  }

  public function getNumberOfHops() {
    $data = [];
    $result = $this->db->query("SELECT COUNT(*) FROM hops");
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
      $data[] = $row;
    }
    return $data;
  }

  public function getAverageHopTime() {
    $data = [];
    $result = $this->db->query("SELECT AVG(rtt1) as AVG1, AVG(rtt2) as AVG2, AVG(rtt3) as AVG3 FROM `hops` WHERE hopNumber > 0");
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
      $data[] = $row;
    }
    return $data;
  }

  public function getTopTraces() {
    $data = [];
    //$result = $this->db->query("SELECT *, count(url) as traceCount FROM `search` GROUP BY url ORDER BY traceCount DESC LIMIT 10");
    $result = $this->db->query("SELECT *, COUNT(url) as traceCount FROM search s1 INNER JOIN hops ON s1.id = hops.searchID INNER JOIN ip_locations USING (ip) WHERE hopNumber = (SELECT MAX(hopNumber) FROM search s2 INNER JOIN hops ON s2.id = hops.searchID INNER JOIN ip_locations USING (ip) WHERE s1.id = s2.id GROUP BY searchID) GROUP BY url LIMIT 10");

    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
      $data[] = $row;
    }
    return $data;
  }

  public function getTraceroute($id) {
    $data = [];
    $result = $this->db->query("SELECT * FROM hops LEFT JOIN ip_locations USING (ip) WHERE searchID = " . $id . " ORDER BY hopNumber");
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
      $data[] = $row;
    }
    return $data;
  }

  public function tracerouteFinished($id) {
    $data = [];
    $result = $this->db->query("SELECT * FROM search WHERE id = " . $id);
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
      $data[] = $row;
    }
    return (count($data) > 0) ? $data[0]['finished'] : true;
  }

  public function updateTracerouteFinished($id) {
    $result = $this->db->query("UPDATE search SET finished = 1 WHERE id = " . $id);
    return $result;
  }


  public function requestIPLocation($url, $ip) {
    $result = $this->getCachedURL($url);

    if (count($result) === 0) {
      //$out = file_get_contents('http://www.freegeoip.net/json/' . $url);

      if (strlen($ip) > 0) {
        $out = file_get_contents('http://ip-api.com/json/' . $ip);
        $this->insertIPLocation($url, $ip, $out);
      }

      if (strlen($url) > 0) {
        $out = file_get_contents('http://ip-api.com/json/' . $url);
        $this->insertIPLocation($url, $ip, $out);
      }
    }
  }
}
