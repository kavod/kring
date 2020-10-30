<?php
error_reporting(-1);
ini_set('display_errors', 'On');

  require_once(__DIR__  . '/../class/kring.inc.php');
  // require_once(__DIR__.'/../../../core/php/core.inc.php');
  // require_once(__DIR__  . '/../php/kring.inc.php');

  // if ($argc<2 || $argc>3)
  //   die("Usage: php deamon.php <configuration file> [url to push]");
  $conf = array(
    "refresh_token" => config::byKey('refresh_token','kring')
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
