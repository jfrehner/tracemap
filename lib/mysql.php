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

class Database {
  private $db = null;

  function Database() {
    $this->connect();
  }

  private function connect() {
    $server = 'localhost'; // this may be an ip address instead
  	$user = 'root';
  	$pass = '';
  	$database = 'tracemap';
  	$this->db = new mysqli($server, $user, $pass, $database);
  }

  public function executeSelect($sql) {
    $result = $this->db->query($sql);
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
  		$data[] = $row;
  	}
    return $data;
  }

  public function insertURL($url) {
    $this->db->query('INSERT INTO search (url, requester_ip) VALUES ("'.$url.'", "'.getUserIP().'")');
    return $this->db->insert_id;
  }

  public function insertTraceroute($searchID, $out) {
    foreach ($out as $key => $value) {
      $parts = explode(" ", trim($value));
      if (is_numeric($parts[0])) {
        $this->db->query('INSERT INTO hops (search_id, hop_number, hostname, ip, rtt1, rtt2, rtt3)
        VALUES ("'.$searchID.'", "'.$parts[0].'", "'.$parts[2].'", "'.str_replace(')', '', str_replace('(', '', $parts[3])).'", "'.$parts[5].'", "'.$parts[8].'", "'.$parts[11].'")');
      } else {
        $this->db->query('INSERT INTO hops (search_id, message)
        VALUES ("'.$searchID.'", "'.$value.'")');
      }
    }
  }

  public function insertIPLocation($hostname, $out) {
    $result = json_decode($out, true);

    if($result['status'] !== 'fail') {
      $result = $this->db->query('INSERT INTO ip_location_cache (ip, hostname, asn, city, country, country_code, isp, org, region, region_name, timezone, zip, longitude, latitude)
      VALUES ("'.$result['query'].'", "'.$hostname.'", "'.$result['as'].'", "'.$result['city'].'", "'.$result['country'].'", "'.$result['countryCode'].'", "'.$result['isp'].'", "'.$result['org'].'",
      "'.$result['region'].'", "'.$result['regionName'].'", "'.$result['timezone'].'", "'.$result['zip'].'", "'.$result['lon'].'", "'.$result['lat'].'")');
    } else {
      // TODO Handle errors
    }
  }

  public function getCachedURL($url) {
    $data = [];
    $result = $this->db->query("SELECT * FROM ip_location_cache WHERE hostname LIKE '".$url."'");
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
    $result = $this->db->query("SELECT AVG(rtt1) as AVG1, AVG(rtt2) as AVG2, AVG(rtt3) as AVG3 FROM `hops` WHERE hop_number > 0");
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
      $data[] = $row;
    }
    return $data;
  }

  public function getTopTraces() {
    $data = [];
    $result = $this->db->query("SELECT *, count(url) as traceCount FROM `search` GROUP BY url ORDER BY traceCount DESC LIMIT 10");
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
      $data[] = $row;
    }
    return $data;
  }

  public function getTraceroute($id) {
    $data = [];
    $result = $this->db->query("SELECT * FROM hops WHERE search_id = " . $id);
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
      $data[] = $row;
    }
    return $data;
  }

}
