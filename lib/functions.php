<?php

/**
 *  Some helper functions used in our project
 */

/**
 *  Checks if a process with a certain PID is running
 *  This method will be used to determine whether a traceroute
 *  command has finished or not.
 */
function isRunning($pid){
  try {
      $result = shell_exec(sprintf("ps %d", $pid));
      if( count(preg_split("/\n/", $result)) > 2){
          return true;
      }
  } catch (Exception $e) {}

  return false;
}

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
