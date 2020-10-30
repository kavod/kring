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
 
 try {
     require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
     include_file('core', 'authentification', 'php');

     if (!isConnect('admin')) {
         throw new Exception(__('401 - Accès non autorisé', __FILE__));
     }

     ajax::init();

   	if (init('action') == 'askCode') {
      $result = kring::askCode(init('username'),init('password'));
      if (array_key_exists('error',$result))
      {
        ajax::error(__("Erreur d'authentification",__FILE__));
      }
      ajax::success($result['phone']);
    } elseif (init('action') == 'authCode')
    {
      $result = kring::authCode(init('verif_code'));
      if ($result)
      {
        ajax::success(__("Identification réussie",__FILE__));
      } else {
        ajax::error(__("Erreur d'authentification",__FILE__));
      }
    }
    throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
    /*     * *********Catch exeption*************** */
} catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}
?>
