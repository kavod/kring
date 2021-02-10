<?php

  declare(ticks = 1);

  error_reporting(-1);
  ini_set('display_errors', 'On');

  define('KRING_CLASS','kring');

  require_once(__DIR__  . '/../core/class/kring.class.php');
  log::add(KRING_CLASS, 'info', "Deamon ".KRING_CLASS."lancé");

  function rm_pidfile( int $signo =0, mixed $signinfo = null) : void
  {
    global $pidfile;
    if (file_exists($pidfile)) {
        unlink($pidfile);
    }
    log::add(KRING_CLASS, 'info', "Deamon ".KRING_CLASS." arrêté");
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
  $devices = array("doorbots"=>array());
  try
  {
    $conf = array(
      "refresh_token" => config::byKey('refresh_token','kring')
    );
    $client = new KRCPA\Clients\krcpaClient();
    $client->auth_refresh($conf['refresh_token']);
    $devices = $client->getDevices();
    foreach($devices['doorbots'] as $device)
    {
      $device->subscribeMotion();
      $device->subscribeRing();
    }
    $wait = 5;
    while (true)
    {
      try {
        $dings = $client->getActiveDings();
        $wait = 5;
      } catch (\Exception $e) {
        log::add(KRING_CLASS,'error',"Deamon: ".$e->getMessage());
        $wait *= 2;
        log::add(KRING_CLASS,'error',"Wait for $wait sec before retry");
        sleep($wait);
        continue;
      }

      // print_r($dings);
      foreach($dings as $ding)
      {
        log::add(KRING_CLASS, 'info', "Ding: ".print_r($ding,true));
        $eqLogic = eqLogic::byLogicalId(intval($ding->getVariable('doorbot_id')), 'kring');
        if (is_object($eqLogic))
        {
          if ($ding->getVariable('kind','')=='motion')
          {
            echo date("Y-m-d H:i:s");
            echo "Doorbot ".$ding->getVariable('doorbot_id').": Motion detected";
            log::add(KRING_CLASS, 'debug', "Doorbot ".$ding->getVariable('doorbot_id').": Motion detected");
            $result = $eqLogic->setInfo('motion',1);
            if (!$result)
              log::add(KRING_CLASS, 'debug', "Doorbot ".$ding->getVariable('doorbot_id').": no motion cmd found");
            if ($eqLogic->getConfiguration('snapOnMotion',false)==1)
            {
              echo date("Y-m-d H:i:s");
              echo "Doorbot ".$ding->getVariable('doorbot_id').": Auto Snapshot";
              log::add(KRING_CLASS, 'debug', "Doorbot ".$ding->getVariable('doorbot_id').": Auto Snapshot");
              $eqLogic->getSnapshot('motion');
            }
          }
          if ($ding->getVariable('kind','')=='ring')
          {
            log::add(KRING_CLASS, 'debug', "Doorbot ".$ding->getVariable('doorbot_id').": Ring detected");
            $result = $eqLogic->setInfo('ring',1);
            if (!$result)
              log::add(KRING_CLASS, 'debug', "Doorbot ".$ding->getVariable('doorbot_id').": no ring cmd found");
            if ($eqLogic->getConfiguration('snapOnRing',false)==1)
            {
              log::add(KRING_CLASS, 'debug', "Doorbot ".$ding->getVariable('doorbot_id').": Auto Snapshot");
              $eqLogic->getSnapshot('ring');
            }
          }
        } else {
          log::add(KRING_CLASS, 'error', "Id: ".$ding->getVariable('doorbot_id')." inconnu");
        }
        echo $ding->toString()."\n";
      }
      sleep(5);
    }
  } catch(\Exception $e)
  {
    foreach($devices['doorbots'] as $device)
    {
      $device->subscribeMotion();
      $device->subscribeRing();
    }
    rm_pidfile();
  }
?>
