<?php

/* This file is part of Kring plugin for Jeedom by Kavod
 *
 * Copyright (C) 2020 Brice GRICHY
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

error_reporting(-1);
ini_set('display_errors', 'On');

 require_once(__DIR__  . '/../../../../core/php/core.inc.php');
 require_once(__DIR__  . '/../php/kring.inc.php');

 define('KRING_LIB_PATH',__DIR__.'/../../3rdparty/krcpa/autoload.php');
 define('KRING_RES_PATH',__DIR__.'/../../resources');
 define('KRCPA_MIN_VERSION','0.1');


 if (!class_exists('KRCPA\Clients\krcpaClient')) {
 	if (file_exists(KRING_LIB_PATH))
 	{
 		require_once(KRING_LIB_PATH);
 	}
 }

 class kring extends eqLogic {
 		const FEATURES = array(
 			'motions_enabled' => 'motion',
 			'ring' => 'ring',
 			'volume' => 'volume',
 			'dnd' => 'dnd',
 			'play_sound' => 'play_sound',
      'snapshots' => 'snapshots'
 		);
   /*     * *************************Attributs****************************** */
   private static $_client = null;
   private static $KRING_DEAMON = __CLASS__.'.deamon.php';
   private static $KRING_DEAMON_PATH = KRING_RES_PATH.'/'.__CLASS__.'.deamon.php';
   private static $ROOT_PATH = __DIR__.'/../../../../';
   private static $RES_PATH = 'plugins/'.__CLASS__.'/resources/';

   private $_device = null;
   //private static $KRING_DEAMON_PATH = KRING_RES_PATH.'/kring.deamon.php';

   /*     * ***********************Methode static*************************** */
   /*     * ----------------------- Dependances ---------------------------- */
   public static function isDepOK()
   {
     if (class_exists('KRCPA\Clients\krcpaClient'))
     {
       if (version_compare(KRCPA\Clients\krcpaClient::getVersion(),KRCPA_MIN_VERSION,'<'))
       {
         $msg = __('Nouvelle version des dépendance requise.
                    Merci de réinstaller les dépendances de '.__CLASS__
                    ,__FILE__);
         log::add(__CLASS__,'error',$msg);
         throw new kringException($msg,402);
       } else {
         return true;
       }
     }
    else {
      $msg = __('Dépendances manquantes. Merci d\'installer les dépendances de kring',__FILE__);
      log::add(__CLASS__,'error',$msg);
      throw new kringException($msg,401);
    }
    return false;
   }

    public static function dependancy_info()
    {
    	  log::add(__CLASS__ . '_update','debug','Checking dependancy');
    		$return = array();
    		$return['log'] = 'kring_update';
    		$return['progress_file'] =  jeedom::getTmpFolder('kring') . '/dependancy_kring_in_progress';
        try {
          if (self::isDepOK()) {
          	log::add(__CLASS__ . '_update','debug','Dependancy: OK');
            $return['state'] = 'ok';
          } else {
          	log::add(__CLASS__ . '_update','debug','Dependancy: KO');
            $return['state'] = 'ko';
          }
        } catch(kringException $e)
        {
        	log::add(__CLASS__ . '_update','debug','Dependancy: KO');
          $return['state'] = 'nok';
        }
     		return $return;
    }

    public static function dependancy_install()
    {
      log::add(__CLASS__, 'info', 'Réinstallation des dépendances');
  		log::remove(__CLASS__ . '_update');
      $path_3rd_party = __DIR__.'/../../3rdparty/';
  		return array(
				'script' => KRING_RES_PATH . '/install.sh ' . $path_3rd_party . ' ' . jeedom::getTmpFolder('kring'),
				'log' => log::getPathToLog(__CLASS__ . '_update')
			);
  	}

    /*     * -----------------------   Deamon   ---------------------------- */
    public static function deamon_info()
    {
  		$return = array();
  		$return['state'] = 'nok';
      $return['launchable'] = 'nok';
  		$pid_file = jeedom::getTmpFolder(__CLASS__) . '/deamon.pid';
  		if (file_exists($pid_file)) {
  			if (posix_getsid(trim(file_get_contents($pid_file)))) {
  				$return['state'] = 'ok';
  			} else {
          $cmd = system::getCmdSudo() . 'rm -rf ' . $pid_file . ' 2>&1 > /dev/null';
  				shell_exec($cmd);
  			}
  		}
      if (self::getClient() != null)
      {
        try {
          self::$_client->getDevices();
          $return['launchable'] = 'ok';
        } catch(\Exception $e)
        {
          log::add(__CLASS__, 'error', 'Lancement démon : '.print_r($e,true));
          $return['launchable_message'] = __('Echec de l\'authentification',__FILE__);
          $return['launchable'] = 'nok';
        }
      } else {
        log::add(__CLASS__, 'error', 'Lancement démon : impossible de trouver le client');
      }

      return $return;
    }

    public static function deamon_start($_debug = false) {
      self::deamon_stop();
      $deamon_info = self::deamon_info();
  		if ($deamon_info['launchable'] != 'ok') {
  			throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
  		}
      $logfile = log::getPathToLog(__CLASS__.'_deamon');
      touch($logfile);
      $logfile = realpath($logfile);

      $cmd  = '/usr/bin/php '.self::$KRING_DEAMON_PATH;
      $cmd .= ' --pid="'.jeedom::getTmpFolder('kring') . '/deamon.pid"';
      $cmd .= ' >> ' . $logfile . ' 2>&1 &';
      log::add(__CLASS__, 'info', 'Lancement démon : ' . $cmd);
      exec($cmd);
  		$i = 0;
  		while ($i < 30) {
  			$deamon_info = self::deamon_info();
  			if ($deamon_info['state'] == 'ok') {
  				break;
  			}
  			sleep(1);
  			$i++;
  		}
      if ($i >= 30)
      {
  			log::add(__CLASS__, 'error', 'Impossible de lancer le démon, relancer le démon en debug et vérifiez la log', 'unableStartDeamon');
  			return false;
  		}
  		message::removeAll(__CLASS__, 'unableStartDeamon');
  		log::add(__CLASS__, 'info', "Deamon ".__CLASS__." lancé");
    }

    public static function deamon_stop() {
      try
      {
        $deamon_info = self::deamon_info();
  			if ($deamon_info['state'] == 'ok')
        {
  				try
          {

          }
          catch (Exception $e)
          {

  				}
  			}
  			$pid_file = jeedom::getTmpFolder(__CLASS__) . '/deamon.pid';
  			if (file_exists($pid_file))
        {
  				$pid = intval(trim(file_get_contents($pid_file)));
  				system::kill($pid);
  			}
        system::kill(self::$KRING_DEAMON);
  			sleep(1);
  		} catch (\Exception $e) {

  		}
    }

    public static function cronHourly() {
      self::cronExec();
    }

    public static function cronExec() {
      log::add(__CLASS__ ,'debug','[Global] Cron Execution');
      foreach (self::byType(__CLASS__) as $eqLogic) {
      if ($eqLogic->getIsEnable())
      {
        $eqLogic->refresh_values();
      }
    }
    /*     * -----------------------   Others   ---------------------------- */

    public static function syncDevices()
    {
      $nb_devices = 0;
      if(self::getClient())
      {
        $devices = self::$_client->getDevices();
        log::add(__CLASS__, 'debug', 'GetDevices: '.print_r($devices,true));
        foreach($devices['doorbots'] as $device)
        {
          try
          {
            log::add(__CLASS__, 'debug', 'Adding device: '.print_r($device,true));
            $id = $device->getVariable('id');
            $device_id = $device->getVariable('device_id');
            $description = $device->getVariable('description');
            $kind = $device->getVariable('kind');
            $battery_life = $device->getVariable('battery_life');

  	  			$eqLogic = self::byLogicalId($id, __CLASS__);
  	  			if (!is_object($eqLogic)) {
              log::add(__CLASS__, 'debug', 'Adding eqLogic '.$id.' does not exist yet. Creating!');
  	  				$eqLogic = new self();
              foreach (jeeObject::all() as $object)
              {
                  if (stristr($description,$object->getName()))
                  {
                    log::add(__CLASS__, 'debug', 'Adding eqLogic '.$id.' Autoadd to object '.$object->getName());
                      $eqLogic->setObject_id($object->getId());
                      break;
                  }
              }
              log::add(__CLASS__, 'debug', "Adding eqLogic $id setLogicalId($id)");
              $eqLogic->setLogicalId($id);
              log::add(__CLASS__, 'debug', "Adding eqLogic $id setName($description)");
  	  				$eqLogic->setName($description);
              log::add(__CLASS__, 'debug', "Adding eqLogic $id setConfiguration('device_id', $device_id)");
  						$eqLogic->setConfiguration('device_id', $device_id);
              log::add(__CLASS__, 'debug', "Adding eqLogic $id setConfiguration('type', $kind)");
  						$eqLogic->setConfiguration('type', $kind);
              log::add(__CLASS__, 'debug', "Adding eqLogic $id setEqType_name(".__CLASS__.")");
  	  				$eqLogic->setEqType_name(__CLASS__);
              log::add(__CLASS__, 'debug', "Adding eqLogic $id setIsVisible(0)");
  	  				$eqLogic->setIsVisible(0);
              log::add(__CLASS__, 'debug', "Adding eqLogic $id setIsEnable(0)");
  	  				$eqLogic->setIsEnable(0);
              log::add(__CLASS__, 'debug', 'Adding eqLogic: '.print_r($eqLogic,true));
  	  				$eqLogic->save();
  						$nb_devices++;
            } else
            {
              log::add(__CLASS__, 'debug', 'Adding eqLogic '.$id.' already exists. Skipping!');
            }
            log::add(__CLASS__, 'debug', "EqLogic $id batteryStatus($battery_life)");
            $eqLogic->batteryStatus($battery_life);
  					$eqLogic->refreshWidget();
          } catch (Exception $e) {
              echo 'Exception reçue : ',  $e->getMessage(), "\n";
          }
        }
        foreach($devices['chimes'] as $device)
        {
          try
          {
            log::add(__CLASS__, 'debug', 'Adding device: '.print_r($device,true));
            $id = $device->getVariable('id');
            $device_id = $device->getVariable('device_id');
            $description = $device->getVariable('description');
            $kind = $device->getVariable('kind');
            $doorbells = ($kind=='chime') ? $device->getLinkedDoorbells() : array();
            $arr_linkedDoorbells = array();
            foreach($doorbells as $doorbell)
            {
              $arr_linkedDoorbells[] = $doorbell->getVariable('id');
            }
            $linkedDoorbells = json_encode($arr_linkedDoorbells);

  	  			$eqLogic = self::byLogicalId($id, __CLASS__);
  	  			if (!is_object($eqLogic)) {
              log::add(__CLASS__, 'debug', 'Adding eqLogic '.$id.' does not exist yet. Creating!');
  	  				$eqLogic = new self();
              foreach (jeeObject::all() as $object)
              {
                  if (stristr($description,$object->getName()))
                  {
                    log::add(__CLASS__, 'debug', 'Adding eqLogic '.$id.' Autoadd to object '.$object->getName());
                      $eqLogic->setObject_id($object->getId());
                      break;
                  }
              }
              log::add(__CLASS__, 'debug', "Adding eqLogic $id setLogicalId($id)");
              $eqLogic->setLogicalId($id);
              log::add(__CLASS__, 'debug', "Adding eqLogic $id setName($description)");
  	  				$eqLogic->setName($description);
              log::add(__CLASS__, 'debug', "Adding eqLogic $id setConfiguration('device_id', $device_id)");
  						$eqLogic->setConfiguration('device_id', $device_id);
              log::add(__CLASS__, 'debug', "Adding eqLogic $id setConfiguration('type', $kind)");
  						$eqLogic->setConfiguration('type', $kind);
              // log::add(__CLASS__, 'debug', "Adding eqLogic $id setConfiguration('linked_devices', $linkedDoorbells)");
  						// $eqLogic->setConfiguration('linked_devices', $linkedDoorbells);
              log::add(__CLASS__, 'debug', "Adding eqLogic $id setEqType_name(".__CLASS__.")");
  	  				$eqLogic->setEqType_name(__CLASS__);
              log::add(__CLASS__, 'debug', "Adding eqLogic $id setIsVisible(0)");
  	  				$eqLogic->setIsVisible(0);
              log::add(__CLASS__, 'debug', "Adding eqLogic $id setIsEnable(0)");
  	  				$eqLogic->setIsEnable(0);
              log::add(__CLASS__, 'debug', 'Adding eqLogic: '.print_r($eqLogic,true));
  	  				$eqLogic->save();
  						$nb_devices++;
            } else
            {
              log::add(__CLASS__, 'debug', 'Adding eqLogic '.$id.' already exists. Skipping!');
            }
  					$eqLogic->refreshWidget();
          } catch (Exception $e) {
              echo 'Exception reçue : ',  $e->getMessage(), "\n";
          }
        }
      }
      return $nb_devices;
    }

    public static function askCode($username='',$password='') {
      $msg = __('Requête du code de vérification',__FILE__);
      log::add(__CLASS__,"debug",$msg);
      try {
        if (self::isDepOK()) {
          config::save('username',$username,__CLASS__);
          config::save('password',$password,__CLASS__);
          self::getClient();
          if (is_null(self::$_client))
          {
            $msg = __('Connexion impossible à Ring.com',__FILE__);
            log::add(__CLASS__,"error",$msg);
            return array('error' => $msg);
          }
          return self::$_client->auth_password();
        }
      } catch (KRCPA\Exceptions\krcpaApiException $e) {
        if ($e->http_code == 412) // 2-step authentification required
        {
          log::add(__CLASS__,"debug","2 step authentification required: ".print_r($e->body,true));
          return $e->body;
        }
        $msg = $e->code_description();
        log::add(__CLASS__,"error",$msg);
        return array('error' => $msg);
      } catch(KRCPA\Exceptions\krcpaException $e)
      {
        $msg = $e->error;
        log::add(__CLASS__,"error",$msg);
        return array('error' => $msg);
      } catch (Exception $e)
      {
        $msg = __('Erreur inconnue: '.print_r($e,true),__FILE__);
        log::add(__CLASS__,"error",$msg);
        return array('error' => $msg);
      }
      $msg = __('Erreur inconnue',__FILE__);
      log::add(__CLASS__,"error",$msg);
      return array('error' => $msg);
    }

    public static function authCode($code)
    {
      self::getClient();
      self::$_client->setVariable('auth_code',$code);
      $result = self::$_client->auth_password();
      config::remove('username',__CLASS__);
      config::remove('password',__CLASS__);
      if (array_key_exists("refresh_token",$result))
      {
        config::save('refresh_token',$result['refresh_token'],__CLASS__);
        return true;
      } else {
        return false;
      }
    }

   /*     * *********************Methode d'instance************************* */
   public function postInsert() {
    $this->loadCmdFromConf('all');
   }

   public function preSave() {
     $device = $this->getDevice();
     $linked_devices = array();
     switch($this->getConfiguration('type'))
     {
       case 'chime':
        $linked_devices = $device->getLinkedDoorbells();
        break;
      case 'doorbell_v4':
       $linked_devices = $device->getLinkedChimes();
       break;
     }
     log::add(__CLASS__, 'debug', $this->getLogicalId().': '. count($linked_devices).' équipement(s) lié(s) trouvé(s)');
     //$doorbells = ($this->getConfiguration('type') =='chime') ? $device->getLinkedDoorbells() : array();
     $arr_linked_devices = array();
     foreach($linked_devices as $linked_device)
     {
       $arr_linked_devices[] = $linked_device->getVariable('id');
     }
     $linkedDevices = json_encode($arr_linked_devices);
     $this->setConfiguration('linked_devices', $linkedDevices);
   }

  public function postSave() {
    log::add(__CLASS__, 'debug', __CLASS__. $this->getLogicalId() . ': Sauvegarde terminée');
    if ($this->getIsEnable())
    {
      log::add(__CLASS__, 'debug', __CLASS__. $this->getLogicalId() . ': Initialisation des cmds binaires');
      if ($this->is_featured('motions_enabled'))
        $this->setInfo('motion',0);
      if ($this->is_featured('ring'))
        $this->setInfo('ring',0);

      log::add(__CLASS__, 'debug', __CLASS__. $this->getLogicalId() . ': Refresh');
      $this->refresh_values();
    }
  }

   public function is_featured($feature)
   {
     $device = $this->getDevice();
     return $device->is_featured($feature);
   }

   public function loadCmdFromConf($cmd='all',$force=0) {
     log::add(__CLASS__, 'debug', "loadCmdFrom($cmd,$force)");
     if ($cmd!='all')
       $cmdSets = array($cmd);
     else {
       foreach($this->getCmd() as $curCmd)
         $curCmd->remove();
       $cmdSets = array('basic');
       foreach(self::FEATURES as $feature => $cmdType)
       {
         log::add(__CLASS__, 'debug', "is featured ".$feature."? ".($this->is_featured($feature)));
         if ($this->is_featured($feature))
         {
           $cmdSets[] = $cmdType;
         }
       }
     }
     $nb_cmd = 0;
     foreach($cmdSets as $cmdSet)
     {
       $filename = dirname(__FILE__) . '/../config/' . $cmdSet.'.json';
       if (!is_file($filename)) {
         throw new \Exception("File $filename does not exist");
       }
       $device = is_json(file_get_contents($filename), array());
       if (!is_array($device) || !isset($device['commands'])) {
         break;
       }
       foreach($device['commands'] as $key => $cmd)
       {
         if (array_key_exists('logicalId',$cmd))
           $id = $cmd['logicalId'];
         else
         {
           if (array_key_exists('name',$cmd))
             $id = $cmd['name'];
           else {
             $id = '';
           }
         }
         $curCmd = $this->getCmd(null, $id);
         if ($force==1 && is_object($curCmd)) {
           $curCmd->remove();
         } elseif (($force == 0) && is_object($curCmd)) {
           unset($device['commands'][$key]);
           continue;
         }
         if (array_key_exists('name',$cmd))
           $cmd['name'] = __($cmd['name'],__FILE__);
       }
       if (count($device['commands'])>0)
       {
         $this->import($device);
       }
       $nb_cmd += count($device['commands']);
     }
     return $nb_cmd;
   }

   public function import($_configuration, $_dontRemove = false) {
     $cmdClass = $this->getEqType_name() . 'Cmd';
     if (isset($_configuration['configuration'])) {
       foreach ($_configuration['configuration'] as $key => $value) {
         $this->setConfiguration($key, $value);
       }
     }
     if (isset($_configuration['category'])) {
       foreach ($_configuration['category'] as $key => $value) {
         $this->setCategory($key, $value);
       }
     }
     $cmd_order = 0;
     foreach($this->getCmd() as $liste_cmd)
     {
       if ($liste_cmd->getOrder()>$cmd_order)
         $cmd_order = $liste_cmd->getOrder()+1;
     }
     $link_cmds = array();
     $link_actions = array();
     $arrayToRemove = [];
     if (isset($_configuration['commands'])) {
       foreach ($_configuration['commands'] as $command) {
         $cmd = null;
         foreach ($this->getCmd() as $liste_cmd) {
           if ((isset($command['logicalId']) && $liste_cmd->getLogicalId() == $command['logicalId'])
           || (isset($command['name']) && $liste_cmd->getName() == $command['name'])) {
             $cmd = $liste_cmd;
             break;
           }
         }
         try {
           if ($cmd === null || !is_object($cmd)) {
             $cmd = new $cmdClass();
             $cmd->setOrder($cmd_order);
             $cmd->setEqLogic_id($this->getId());
           } else {
             $command['name'] = $cmd->getName();
             if (isset($command['display'])) {
               unset($command['display']);
             }
           }
           utils::a2o($cmd, $command);
           $cmd->setConfiguration('logicalId', $cmd->getLogicalId());
           $cmd->save();
           log::add(__CLASS__, 'debug', $cmd->getLogicalId().' sauvegardé: '.print_r($cmd,true));
           if (isset($command['value'])) {
             $link_cmds[$cmd->getId()] = $command['value'];
           }

           if (isset($command['configuration']) && isset($command['configuration']['updateCmdId'])) {
             $link_actions[$cmd->getId()] = $command['configuration']['updateCmdId'];
           }
           $cmd_order++;
         } catch (Exception $exc) {
           log::add(__CLASS__,'error','Error importing '.$command['name']);
           throw $exc;
         }
       }
     }
     if (count($link_cmds) > 0) {
       foreach ($this->getCmd() as $eqLogic_cmd) {
         foreach ($link_cmds as $cmd_id => $link_cmd) {
           if ($link_cmd == $eqLogic_cmd->getLogicalId()) { // diff kkasa
             $cmd = cmd::byId($cmd_id);
             if (is_object($cmd)) {
               $cmd->setValue($eqLogic_cmd->getId());
               $cmd->save();
             }
           }
         }
       }
     }
     if (count($link_actions) > 0) {
       foreach ($this->getCmd() as $eqLogic_cmd) {
         foreach ($link_actions as $cmd_id => $link_action) {
           if ($link_action == $eqLogic_cmd->getName()) {
             $cmd = cmd::byId($cmd_id);
             if (is_object($cmd)) {
               $cmd->setConfiguration('updateCmdId', $eqLogic_cmd->getId());
               $cmd->save();
             }
           }
         }
       }
     }
     $this->save();
   }

   public function refresh_values()
   {
     $device = $this->getDevice();

     $changed = false;
     if ($this->is_featured('dnd'))
     {
        $changed = $this->setInfo('getDnd',$device->getDoNotDisturb()) || $changed;
     }
     if ($this->is_featured('volume'))
     {
        $changed = $this->setInfo('getVolume',$device->getVolume()) || $changed;
     }

     // Battery update
     $battery_life = $device->getVariable('battery_life',-1);
     if ($battery_life>-1)
     {
       $this->batteryStatus($battery_life);
     }

     if ($changed) {
       $this->refreshWidget();
     }
   }

   public function play_sound($kind='ding')
   {
     log::add(__CLASS__, 'debug', $this->getLogicalId().' play_sound: '.$kind);
     $device = $this->getDevice();
     $device->playSound($kind);
     $this->refreshWidget();
   }

   /*     * **********************Getteur Setteur*************************** */
   public static function getClient()
   {
     if (class_exists('KRCPA\Clients\krcpaClient'))
     {
       if (self::$_client == null)
       {
         $conf = array(
           "username" => config::byKey('username',$_plugin=__CLASS__,$_default=''),
           "password" => config::byKey('password',$_plugin=__CLASS__,$_default=''),
           "refresh_token" => config::byKey('refresh_token', __CLASS__,$_default='')
         );
         self::$_client = new KRCPA\Clients\krcpaClient($conf);
       }
     } else {
       log::add(__CLASS__,'error','Dépendances manquantes');
     }
     return self::$_client;
   }

   public function getDevice() {
     if ($this->_device == null) {
       $client = self::getClient();
       $this->_device = $client->getDeviceById(intval($this->getLogicalId()));
       if (!is_object($this->_device))
       {
           log::add(__CLASS__, 'error', $this->getLogicalId().' introuvable');
       }
     }
     return $this->_device;
   }

  public function setInfo($cmd_name,$value)
  {
    $cmd = $this->getCmd('info',$cmd_name);
    if (is_object($cmd)) {
      $cmd->refresh();
      $changed = $this->checkAndUpdateCmd($cmd_name, $value);
      log::add(__CLASS__,'debug','set: '.$cmd->getName().' to '. $value.' ('.((int)$changed).')');
      $cmd->event($value,null,0);
      return $changed;
    }
    log::add(__CLASS__,'error','Commande '.$cmd_name.' inconnue');
    return false;
  }

  public function getInfo($cmd_name,$default=null)
  {
    $cmd = $this->getCmd('info',$cmd_name);
    if (is_object($cmd)) {
      $cmd->refresh();
      return $cmd->getValue();
    }
    log::add(__CLASS__,'error','Commande '.$cmd_name.' inconnue');
    return $default;
  }

  public function getImage() {
    try {
      $device = $this->getDevice();
      $path = '/docs/images/'.$device->getVariable('kind','').'.png';
      if (file_exists(__DIR__.'/../..'.$path))
        return 'plugins/' . __CLASS__ . $path;
      else {
        return parent::getImage();
      }
    } catch (KRCPA\Exceptions\krcpaApiException $e)
    {
      return 'plugins/' . __CLASS__ . $path;
    }
  }

  public function setDoNotDisturb($time=60) {
    $device = $this->getDevice();
    return $device->setDoNotDisturb($time);
  }

  public function setVolume($vol) {
    $device = $this->getDevice();
    return $device->setVolume($vol);
  }

  public function getSnapPath() {
    $device = $this->getDevice();
    return self::$RES_PATH.$device->getVariable('id');
  }

  public function getSnapshot($event='onDemand') {
    $device = $this->getDevice();
    // $root_path = __DIR__.'/../../../../';
    // $res_path = 'plugins/'.__CLASS__.'/resources/';
    $relative_path = sprintf(
      '%s/%s_%s.jpg',
      $this->getSnapPath(),
      date('U'),
      $event
    );
    //$relative_path = $res_path.$device->getVariable('id').'/'.date('U').'_'.$event.'.jpg';
    $snap_path = $device->getSnapshot(self::$ROOT_PATH.$relative_path);
    $this->setInfo('snapshot',$relative_path);
  }

  public function getSnapshotList()
  {
    $path = self::$ROOT_PATH.$this->getSnapPath();
    if (!file_exists($path))
      mkdir($path);
    $files = scandir($path);
    $result = array();
    foreach($files as $file_path)
    {
      if (substr($file_path,0,1)=='.')
        continue;
      if (preg_match('/(\d+)_(\w+)\.jpg/',$file_path,$matches))
      {
        $imageData = base64_encode(file_get_contents($path.'/'.$file_path));
        $src = 'data: '.mime_content_type($path.'/'.$file_path).';base64,'.$imageData;
        $result[] = array(
          'deviceName' => $this->getName(),
          'device' => $this->getLogicalId(),
          'timestamp' => $matches[1],
          'event' => $matches[2],
          'file_path' => $this->getSnapPath()."/".$file_path,
          'imageData' => $src
        );
      }
    }
    return $result;
  }
 }


 class kringCmd extends cmd {

  /*     * *************************Attributs****************************** */


  /*     * ***********************Methode static*************************** */


  /*     * *********************Methode d'instance************************* */
  public function execute($_options = array()) {
    if ($this->getType() == 'info') {
      return;
    }
    if ($this->getType() == '') {
      return '';
    }
    $eqLogic = $this->getEqLogic();
    if ($this->getLogicalId() == 'setDnd') {
      $eqLogic->setDoNotDisturb($_options['message']);
      $cmdGetDnd = $eqLogic->getCmd('info', 'getDnd');
      if (is_object($cmdGetDnd)) {
        log::add(__CLASS__,'debug','set: '.$cmdGetDnd->getName().' returnStateTime to: '.$_options['message']);
        $cmdGetDnd->setReturnStateValue(0);
        $cmdGetDnd->setReturnStateTime(intval($_options['message']));
      }
      $eqLogic->refresh_values();
    } elseif ($this->getLogicalId() == 'refresh') {
      $eqLogic->refresh_values();
    } elseif ($this->getLogicalId() == 'setVolume') {
      $eqLogic->setVolume($_options['slider']);
      $cmdGetVol = $eqLogic->getCmd('info', 'getVolume');
      $eqLogic->refresh_values();
    } elseif ($this->getLogicalId() == 'play_sound') {
      $kind = ($_options['select']=="0") ? 'motion' : 'ding';
      $eqLogic->play_sound($kind);
    } elseif ($this->getLogicalId() == 'takeSnapshot') {
      $eqLogic->getSnapshot();
    }

  }
  /*     * **********************Getteur Setteur*************************** */
  public function setReturnStateTime($time)
  {
    $this->setConfiguration('returnStateTime',$time);
    $this->save();
  }

  public function setReturnStateValue($value)
  {
    $this->setConfiguration('returnStateValue',$value);
    $this->save();
  }
}

class kringException extends \Exception
{
  public $http_code;
  public $error;

  function __construct($message,$code)
  {
    $this->http_code = $code;
    $this->error = $message;
    parent::__construct($message, $code);
  }
}
?>
