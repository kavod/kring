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
   /*     * *************************Attributs****************************** */
   private static $_client = null;

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
				'script' => __DIR__ . '/../../resources/install.sh ' . $path_3rd_party . ' ' . jeedom::getTmpFolder('kring'),
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

    }

    public static function deamon_stop() {

    }
    /*     * -----------------------   Others   ---------------------------- */
    public static function getClient()
    {
      if (class_exists('KRCPA\Clients\krcpaClient'))
      {
        if (self::$_client == null)
        {
          $conf = array(
            "username" => config::byKey('username', __CLASS__),
            "password" => config::byKey('password', __CLASS__)
          );
          self::$_client = new KRCPA\Clients\krcpaClient($conf);
        }
      }
      return self::$_client;
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


   /*     * **********************Getteur Setteur*************************** */
 }

 class kringCmd extends cmd {

  /*     * *************************Attributs****************************** */


  /*     * ***********************Methode static*************************** */


  /*     * *********************Methode d'instance************************* */
  public function execute($_options = array()) {

  }

  /*     * **********************Getteur Setteur*************************** */

}

?>
