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
 include_file('core', 'authentification', 'php');
 if (!isConnect()) {
     include_file('desktop', '404', 'php');
     die();
 }
 ?>
<h4>{{S'identifier}}</h1>
<div>
 <a class="btn btn-success btn-xs" id="bt_sendPassword">
   <i class="far fa-comment-alt icon-white"></i>{{ Demander code}}
 </a>
</div>
<?php include_file('desktop', 'kring', 'js', 'kring');?>
