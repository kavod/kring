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

 require_once(__DIR__  . '/../../../../core/php/core.inc.php');
 require_once(__DIR__  . '/../php/kring.inc.php');

 define('KRING_LIB_PATH',__DIR__.'/../../3rdparty/krcpa/autoload.php');
 define('KRING_RES_PATH',__DIR__.'/../../resources');
 // define('KRING_DEAMON',__CLASS__.'.deamon.php');
 // define('KRING_DEAMON_PATH',KRING_RES_PATH.'/'.KRING_DEAMON);
 define('KRCPA_MIN_VERSION','0.1');

 error_reporting(-1);
 ini_set('display_errors', 'On');

 if (!class_exists('KRCPA\Clients\krcpaClient')) {
 	if (file_exists(KRING_LIB_PATH))
 	{
 		require_once(KRING_LIB_PATH);
 	}
 }

 class kring extends eqLogic {
 		const FEATURES = array(
 			'motions_enabled' => 'motion'
 		);
   /*     * *************************Attributs****************************** */
   private static $_client = null;
   private static $KRING_DEAMON = __CLASS__.'.deamon.php';
   private static $KRING_DEAMON_PATH = KRING_RES_PATH.'/'.__CLASS__.'.deamon.php';

   private $_device = null;
   //private static $KRING_DEAMON_PATH = KRING_RES_PATH.'/kring.deamon.php';

   /*     * ***********************Methode static*************************** */
   /*     * ----------------------- Dependances ---------------------------- */

    public static function dependancy_info()
    {
    	  log::add(__CLASS__ . '_update','debug','Checking dependancy');
    		$return = array();
    		$return['log'] = 'kring_update';
    		$return['progress_file'] =  jeedom::getTmpFolder('kring') . '/dependancy_kring_in_progress';
        if (class_exists('KRCPA\Clients\krcpaClient'))
        {
          try
          {
            if (version_compare(KRCPA\Clients\krcpaClient::getVersion(),KRCPA_MIN_VERSION,'<'))
            {
              log::add(__CLASS__,'error',
              __('Nouvelle version des dépendance requise. Merci de réinstaller les dépendances de kring',__FILE__)
  						);
              $return['state'] = 'nok';
            } else {
              $return['state'] = 'ok';
            }
  				}
  				catch (Exception $e)
  				{
  		   		$return['state'] = 'nok';
  				}
        } else
        {
          $return['state'] = 'nok';
        }
        log::add(__CLASS__,'debug','Dependancy_info: '.print_r($return,true));
     		return $return;
    }

    public static function dependancy_install()
    {
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
  				shell_exec(system::getCmdSudo() . 'rm -rf ' . $pid_file . ' 2>&1 > /dev/null');
  			}
  		}
      if (self::getClient() != null)
      {
        if (self::$_client->isAuth())
        {
          $return['launchable'] = 'ok';
        }
      }

      return $return;
    }

    public static function deamon_start($_debug = false) {
      self::deamon_stop();
      $deamon_info = self::deamon_info();
  		if ($deamon_info['launchable'] != 'ok') {
  			throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
  		}
      $cmd  = '/usr/bin/php '.self::$KRING_DEAMON_PATH;
      $cmd .= ' --pid="'.jeedom::getTmpFolder('kring') . '/deamon.pid"';
      log::add(__CLASS__, 'info', 'Lancement démon : ' . $cmd);
      exec($cmd . ' >> ' . log::getPathToLog(__CLASS__) . ' 2>&1 &');
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
    /*     * -----------------------   Others   ---------------------------- */

    public static function syncDevices()
    {
      $nb_devices = 0;
      if(self::getClient())
      {
        $devices = self::$_client->getDevices()['doorbots'];
        log::add(__CLASS__, 'debug', print_r($devices,true));
        foreach($devices as $device)
        {
          try
          {
            $id = $device->getVariable('id');
            $device_id = $device->getVariable('device_id');
            $description = $device->getVariable('description');
            $kind = $device->getVariable('kind');
            $battery_life = $device->getVariable('battery_life');

  	  			$eqLogic = self::byLogicalId($id, __CLASS__);
  	  			if (!is_object($eqLogic)) {
  	  				$eqLogic = new self();
              foreach (jeeObject::all() as $object)
              {
                  if (stristr($description,$object->getName()))
                  {
                      $eqLogic->setObject_id($object->getId());
                      break;
                  }
              }
              $eqLogic->setLogicalId($id);
  	  				$eqLogic->setName($description);
  						$eqLogic->setConfiguration('device_id', $device_id);
  						$eqLogic->setConfiguration('type', $kind);
              $eqLogic->batteryStatus($battery_life);
  	  				$eqLogic->setEqType_name(__CLASS__);
  	  				$eqLogic->setIsVisible(0);
  	  				$eqLogic->setIsEnable(0);
  	  				$eqLogic->save();
  						$nb_devices++;
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
      config::save('username',$username,__CLASS__);
      config::save('password',$password,__CLASS__);
      self::getClient();
      return self::$_client->auth_password();
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

   public function postSave() {
     if ($this->getIsEnable())
     {
       if ($this->is_featured('motions_enabled'))
       {
         $curCmd = $this->getCmd(null, 'motion');
         if (is_object($curCmd))
         {
           log::add(__CLASS__, 'debug', 'motion initialisé à 0');
           $curCmd->event(0);
         }
       }
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
           if (isset($command['value'])) {
             $link_cmds[$cmd->getId()] = $command['value'];
           }
           if (isset($command['configuration']) && isset($command['configuration']['updateCmdId'])) {
             $link_actions[$cmd->getId()] = $command['configuration']['updateCmdId'];
           }
           $cmd_order++;
         } catch (Exception $exc) {
           log::error(__CLASS__,'error','Error importing '.$command['name']);
           throw $exc;
         }
         $cmd->event('');
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

   /*     * **********************Getteur Setteur*************************** */
   public static function getClient()
   {
     if (class_exists('KRCPA\Clients\krcpaClient'))
     {
       if (self::$_client == null)
       {
         $conf = array(
           "refresh_token" => config::byKey('refresh_token', __CLASS__)
         );
         self::$_client = new KRCPA\Clients\krcpaClient($conf);
       }
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
    $cmd = $this->getCmd(null,$cmd_name);
    if (is_object($cmd)) {
      $cmd->refresh();
      $changed = $this->checkAndUpdateCmd($cmd_name, $value);
      log::add(__CLASS__,'debug','set: '.$cmd->getName().' to '. $value);
      $cmd->event($value,null,0);
      return $changed;
    }
    return false;
  }

  public function getInfo($cmd_name,$default=null)
  {
    $cmd = $this->getCmd(null,$cmd_name);
    if (is_object($cmd)) {
      $cmd->refresh();
      return $cmd->getValue();
    }
    return $default;
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

  }

  /*     * **********************Getteur Setteur*************************** */

}

?>
