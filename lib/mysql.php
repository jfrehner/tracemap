<?php

/**
 *  Tries to get the IP of a user.
 *  Will be stored in the DB but is not used yet.
 */
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
    $this->db->query('INSERT INTO search (url, requesterIP) VALUES ("'.$this->db->real_escape_string($url).'", "'.$this->db->real_escape_string(getUserIP()).'")');
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
      VALUES ("'.$this->db->real_escape_string($searchID).'", "'.$this->db->real_escape_string($data['message']).'")');
    } else {
      $this->db->query('INSERT INTO hops (searchID, hopNumber, hostname, ip, rtt1, rtt2, rtt3)
      VALUES ("'.$this->db->real_escape_string($searchID).'", "'.$this->db->real_escape_string($data['hopNumber']).'", "'.$this->db->real_escape_string($data['hostname']).'", "'.$this->db->real_escape_string($data['ip']).'", "'.$this->db->real_escape_string($data['rtt1']).'", "'.$this->db->real_escape_string($data['rtt2']).'", "'.$this->db->real_escape_string($data['rtt3']).'")
      ON DUPLICATE KEY UPDATE hopNumber = "'.$this->db->real_escape_string($data['hopNumber']).'", hostname = "'.$this->db->real_escape_string($data['hostname']).'", ip = "'.$this->db->real_escape_string($data['ip']).'", rtt1= "'.$this->db->real_escape_string($data['rtt1']).'", rtt2 = "'.$this->db->real_escape_string($data['rtt2']).'", rtt3 = "'.$this->db->real_escape_string($data['rtt3']).'"');
      $this->requestIPLocation($data['hostname'], $data['ip']);
    }
  }

  /**
   * Inserts the response of the ip-api.com API into the DB for faster access.
   *
   * @param  String   $hostname The hostname of the hop.
   * @param  String   $ip       The ip address of the hop.
   * @param  Object   $out      The JSON-object of the API response.
   */
  public function insertIPLocation($hostname, $ip, $out) {
    $result = json_decode($out, true);

    if($result['status'] !== 'fail') {
      $result = $this->db->query('INSERT INTO ip_locations (ip, hostname, asn, city, country, countryCode, isp, org, region, regionName, timezone, zip, longitude, latitude,status)
      VALUES ("'.$this->db->real_escape_string($result['query']).'", "'.$this->db->real_escape_string($hostname).'", "'.$this->db->real_escape_string($result['as']).'", "'.$this->db->real_escape_string($result['city']).'", "'.$this->db->real_escape_string($result['country']).'", "'.$this->db->real_escape_string($result['countryCode']).'", "'.$this->db->real_escape_string($result['isp']).'", "'.$this->db->real_escape_string($result['org']).'",
      "'.$this->db->real_escape_string($result['region']).'", "'.$this->db->real_escape_string($result['regionName']).'", "'.$this->db->real_escape_string($result['timezone']).'", "'.$this->db->real_escape_string($result['zip']).'", "'.$this->db->real_escape_string($result['lon']).'", "'.$this->db->real_escape_string($result['lat']).'", 1)');
    } else {
      $result = $this->db->query('INSERT INTO ip_locations (ip, hostname, status)
      VALUES ("'.$this->db->real_escape_string($ip).'", "'.$this->db->real_escape_string($hostname).'", 0)');
    }
  }

  /**
   * Checks if a given hostname is already in our location table.
   *
   * @param  String   $url  The hostname to check if already in the DB.
   */
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

  /**
  *   Get the number of traceroutes we executed so far.
  */
  public function getNumberOfTraces() {
    $data = [];
    $result = $this->db->query("SELECT COUNT(*) FROM search");
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
      $data[] = $row;
    }
    return $data;
  }

  /**
  *   Get the number of hops we visited so far.
  */
  public function getNumberOfHops() {
    $data = [];
    $result = $this->db->query("SELECT COUNT(*) FROM hops");
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
      $data[] = $row;
    }
    return $data;
  }

  /**
  *   Get the average time to reach hops.
  */
  public function getAverageHopTime() {
    $data = [];
    $result = $this->db->query("SELECT AVG(rtt1) as AVG1, AVG(rtt2) as AVG2, AVG(rtt3) as AVG3 FROM `hops` WHERE hopNumber > 0");
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
      $data[] = $row;
    }
    return $data;
  }

  /**
  *   Get the average number of hops per route.
  */
  public function getAverageHopsPerRoute() {
    $data = [];
    $result = $this->db->query("SELECT ROUND(AVG(hopCount), 1) AS averageHopCount FROM hops h INNER JOIN (
        SELECT searchID, COUNT(*) AS hopCount FROM `hops` WHERE hostname NOT LIKE '' OR ip NOT LIKE '' GROUP BY searchID
    ) counts ON counts.searchID = h.searchID
    WHERE hopNumber = 0
    GROUP BY hopNumber");
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
      $data[] = $row;
    }
    return $data;
  }

  /**
  *   Get an array of all hop times.
  */
  public function getAllHopTimes() {
    $data = [];
    //$result = $this->db->query("SELECT *, COUNT(url) as traceCount FROM search s1 INNER JOIN hops ON s1.id = hops.searchID INNER JOIN ip_locations USING (ip) WHERE hopNumber = (SELECT MAX(hopNumber) FROM search s2 INNER JOIN hops ON s2.id = hops.searchID INNER JOIN ip_locations USING (ip) WHERE s1.id = s2.id GROUP BY searchID) GROUP BY url ORDER BY tracecount DESC LIMIT 10");
    $result = $this->db->query("SELECT ROUND(rtt1, 1) as rtt1, ROUND(rtt2, 1) as rtt2, ROUND(rtt3, 1) as rtt3 FROM hops WHERE rtt1 IS NOT NULL AND rtt1 > 0 AND rtt2 IS NOT NULL AND rtt2 > 0 AND rtt3 IS NOT NULL AND rtt3 > 0");
    while($row = $result->fetch_array(MYSQLI_ASSOC)) {
      $data[] = $row['rtt1'];
      $data[] = $row['rtt2'];
      $data[] = $row['rtt3'];
    }
    return $data;
  }

  /**
   * Get Hop times of a certain traceroute by ID.
   *
   * @param  Int   $id The ID of the search.
   */
  public function getHopTimesOfID($id) {
    $data = [];
    $result = $this->db->query("SELECT rtt1, rtt2, rtt3, hostname FROM hops WHERE searchID = " . $id . " AND rtt1 IS NOT NULL AND rtt1 > 0 AND rtt2 IS NOT NULL AND rtt2 > 0 AND rtt3 IS NOT NULL AND rtt3 > 0");
    while($row = $result->fetch_array(MYSQLI_ASSOC)) {
      $data[] = $row;
    }
    return $data;
  }

  /**
   * Get the most traced URLs
   */
  public function getTopTraces() {
    $data = [];
    $result = $this->db->query("SELECT *, COUNT(url) as traceCount FROM search s1 INNER JOIN hops ON s1.id = hops.searchID INNER JOIN ip_locations USING (ip) WHERE hopNumber = (SELECT MAX(hopNumber) FROM search s2 INNER JOIN hops ON s2.id = hops.searchID INNER JOIN ip_locations USING (ip) WHERE s1.id = s2.id GROUP BY searchID) GROUP BY url ORDER BY tracecount DESC LIMIT 10");

    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
      $data[] = $row;
    }
    return $data;
  }

  /**
   * Get all hops of a traceroute by ID.
   *
   * @param  Int   $id The ID of the search.
   */
  public function getTraceroute($id) {
    $data = [];
    $result = $this->db->query("SELECT * FROM hops LEFT JOIN ip_locations USING (ip) WHERE searchID = " . $id . " ORDER BY hopNumber");
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
      $data[] = $row;
    }
    return $data;
  }

  /**
   * Get a list of all countries and a count of how many hops they have.
   */
  public function getCountryCount() {
    $data = [];
    $result = $this->db->query("SELECT country, countryCode, COUNT(*) as count FROM `ip_locations` WHERE country NOT LIKE '' GROUP BY country");
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
      $data[] = $row;
    }
    return $data;
  }

  /**
   * Check the status of a certain traceroute.
   *
   * @param  Int   $id The ID of the search.
   */
  public function tracerouteFinished($id) {
    $data = [];
    $result = $this->db->query("SELECT * FROM search WHERE id = " . $id);
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
      $data[] = $row;
    }
    return (count($data) > 0) ? $data[0]['finished'] : true;
  }

  /**
   * Updates the status of a traceroute to finished.
   *
   * @param  Int   $id The ID of the search.
   */
  public function updateTracerouteFinished($id) {
    $result = $this->db->query("UPDATE search SET finished = 1 WHERE id = " . $id);
    return $result;
  }

  /**
   * Get the location of a hop by URL or IP.
   *
   * @param  String   $url  The URL of the hop.
   * @param  String   $ip   The IP of the hop.
   */
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
