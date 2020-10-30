<?php

  declare(ticks = 1);

  error_reporting(-1);
  ini_set('display_errors', 'On');

  require_once(__DIR__  . '/../core/class/kring.class.php');

  function rm_pidfile( int $signo =0, mixed $signinfo = null) : void
  {
    global $pidfile;
    if (file_exists($pidfile)) {
        unlink($pidfile);
    }
    exit(1);
  }

  $pidfile = '';
  $options = getopt("",array('pid:'));
  if (array_key_exists('pid',$options))
  {
    $pidfile = $options['pid'];
    if (file_exists($pidfile))
      unlink($pidfile);
    $handle = fopen($pidfile, 'x');
    if (!$handle) {
        return false;
    }
    $pid = getmypid();
    fwrite($handle, $pid);
    fclose($handle);
  }
  pcntl_signal(SIGTERM, "rm_pidfile");
  pcntl_signal(SIGINT, "rm_pidfile");
  try
  {
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
  } catch(\Exception $e)
  {
    rm_pidfile();
  }
?>
