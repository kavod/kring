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
 require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
 require_once dirname(__FILE__) . '/../core/php/kring.inc.php';

 function kring_install() {
   $uuid = kring::guidv4();
   config::save('uuid', $uuid,'kring');
 }

 function kring_update() {
   if (config::byKey('uuid', 'kring','-1')=='-1')
   {
     $uuid = kring::guidv4();
     config::save('uuid', $uuid,'kring');
   }

   $kring_version = config::byKey('version','kkasa','0.1');
   log::add('kring', 'debug', "Update kring from ".$kring_version . " to ".KKASA_VERSION);
   $plugin = plugin::ById('kkasa');
   try {
     $plugin->dependancy_install();
   } catch (\Exception $e)
   {
     log::add('kring', 'error', "Error during dependancy install ".print_r($e,true));
   }
   $eqLogics = eqLogic::byType('kring');
   $changed = false;

   if (version_compare($kring_version,'0.2','<'))
   {
     foreach ($eqLogics as $eqLogic) {
       if ($eqLogic->is_featured('snapshots')) {
         $eqLogic->setConfiguration('maxSnapshots', 10);
         $eqLogic->save();
       }
     }
   }

 }
 function kring_remove() {

 }
