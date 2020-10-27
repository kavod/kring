<?php
  require_once ('autoload.php');

  if ($argc<2 || $argc>3)
    die("Usage: php deamon.php <configuration file> [url to push]");

  require($argv[1]);
  $conf = array(
    "username" => $username,
    "password" => $password,
    "auth_code" => $auth_code,
    "refresh_token" => $refresh_token
  );
  $client = new KRCPA\Clients\krcpaClient($conf);
  $client->auth_refresh();
  while (true)
  {
    $dings = $client->getActiveDings();
    foreach($dings as $ding)
    {
      if ($argc==3)
      {
        $url = $argv[2].'&value='.urlencode($ding->toString());
        echo $url."\n";
        $ch = curl_init();
        $opts = array(
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HEADER         => TRUE,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_SSL_VERIFYPEER => TRUE,
            CURLOPT_HTTPHEADER     => array(
                "Content-Type: application/json"
              )
        );
        $opts[CURLOPT_HTTPGET] = true;
        $opts[CURLOPT_URL] = $url;

        curl_setopt_array($ch, $opts);
        $result = curl_exec($ch);
        $errno = curl_errno($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        echo $http_code."\n";
        curl_close($ch);
      } else {
        echo $ding->toString()."\n";
      }
    }
    sleep(5);
  }
?>
