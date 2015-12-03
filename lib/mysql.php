<?php

class Database {
  private $db = null;

  function Database() {
    $this->connect();
    echo "Constructed!";
  }

  private function connect() {
    $server = 'localhost'; // this may be an ip address instead
  	$user = 'user';
  	$pass = 'pass';
  	$database = 'slim_db';
  	$this->db = new mysqli($server, $user, $pass, $database);
  }

  public function executeSelect($sql) {
    $result = $db->query( 'SELECT id, name, job FROM friends;' );
    while ( $row = $result->fetch_array(MYSQLI_ASSOC) ) {
  		$data[] = $row;
  	}
    return $data;
  }
}
