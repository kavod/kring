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
 // error_reporting(-1);
 // ini_set('display_errors', 'On');

if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
 }
 $plugin = plugin::byId('kring');
 include_file('desktop', 'kring', 'css','kring');
 sendVarToJS('eqType', $plugin->getId());
 $eqLogics = eqLogic::byType($plugin->getId());

 $debug = (intval(log::getLogLevel('kring')) <=100);
 sendVarToJS('kringDebug', $debug);
 ?>

<div class="row row-overflow">
  <!-- Volet gauche -->
  <div class="col-lg-2 col-md-3 col-sm-4">
    <div class="bs-sidebar">
      <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
        <li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
<?php
  foreach ($eqLogics as $eqLogic) {
    $opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
    echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '" style="' . $opacity .'"><a>' . $eqLogic->getHumanName(true) . '</a></li>';
  }
?>
      </ul>
    </div>
  </div>

  <div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">
    <!-- Boutons de gestion -->
    <legend><i class="fa fa-cog"></i>  {{Gestion}}</legend>
    <div class="eqLogicThumbnailContainer">
     <div class="cursor eqLogicAction" id="btSync" style="text-align: center; background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
       <i class="fa fa-search" style="font-size : 6em;color:#767676;"></i>
       <br />
       <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676">{{Identifier mes équipements}}</span>
     </div>
      <div class="cursor eqLogicAction" data-action="gotoPluginConf" style="text-align: center; background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
        <i class="fa fa-wrench" style="font-size : 6em;color:#767676;"></i>
        <br />
        <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676">{{Configuration}}</span>
      </div>
    </div>

    <!-- Liste des équipements -->
    <legend><i class="fa fa-table"></i> {{Mes périphériques}}</legend>
    <div class="eqLogicThumbnailContainer">
      <?php
      foreach ($eqLogics as $eqLogic) {
      	$opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
      	echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" style="text-align: center; background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;' . $opacity . '" >';
      	echo '<img src="' . $eqLogic->getImage() . '" height="105" width="95" />';
      	echo "<br>";
      	echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;">' . $eqLogic->getHumanName(true, true) . '</span>';
      	echo '</div>';
      }
      ?>
    </div>
  </div>

  <!-- Vue équipement -->
  <div class="col-lg-10 col-md-9 col-sm-8 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
    <a class="btn btn-success eqLogicAction pull-right" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
    <a class="btn btn-danger eqLogicAction pull-right" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
    <a class="btn btn-default eqLogicAction pull-right" data-action="configure"><i class="fa fa-cogs"></i> {{Configuration avancée}}</a>
    <ul class="nav nav-tabs" role="tablist">
      <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
      <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-tachometer"></i> {{Equipement}}</a></li>
      <li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> {{Commandes}}</a></li>
      <li role="presentation"><a href="#snaptab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-camera"></i> {{Captures}}</a></li>
    </ul>
    <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
      <div role="tabpanel" class="tab-pane active" id="eqlogictab">
        <br/>
        <div class="col-lg-2">
          <img id="eqLogic_img" height="172" width="150" />
        </div>
        <div class="col-lg-6">
          <form class="form-horizontal">
            <fieldset>
              <div class="form-group">
                  <label class="col-sm-4 control-label">{{Nom de l'équipement}}</label>
                  <div class="col-sm-8">
                      <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                      <input type="text" class="eqLogicAttr form-control" data-l1key="logicalId" style="display : none;" />
                      <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}"/>
                  </div>
              </div>
              <div class="form-group">
                  <label class="col-sm-4 control-label" >{{Objet parent}}</label>
                  <div class="col-sm-8">
                      <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                          <option value="">{{Aucun}}</option>
  <?php
  foreach (jeeObject::all() as $object) {
    echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
  }
  ?>
                     </select>
                 </div>
             </div>
             <div class="form-group">
                  <label class="col-sm-4 control-label">{{Catégorie}}</label>
                  <div class="col-sm-8">
                   <?php
                      foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                      echo '<label class="checkbox-inline">';
                      echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                      echo '</label>';
                      }
                    ?>
                 </div>
             </div>
            <div class="form-group">
              <label class="col-sm-4 control-label"></label>
              <div class="col-sm-8">
                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
              </div>
            </div>
            <div class="form-group">
                <label class="col-sm-4 control-label"></label>
                <div class="col-sm-8">
                  <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="snapOnRing" checked/>{{Capture auto lors d'une sonnette}}</label>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-4 control-label"></label>
                <div class="col-sm-8">
                  <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="snapOnMotion" checked/>{{Capture auto lors d'un mouvement}}</label>
                </div>
            </div>
            <div class="form-group kringFeatSnap">
                <label class="col-sm-4 control-label">{{Nombre maximum de snapshots}}</label>
                <div class="col-sm-8">
                  <input class="eqLogicAttr configuration form-control" type="number" data-l1key="configuration" data-l2key="maxSnapshots" min="1" />
                </div>
            </div>
            <div class="form-group kringDebug">
                <label class="col-sm-4 control-label">{{Identifiant Equipement}}</label>
                <div class="col-sm-8">
                    <input disabled class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="device_id"/>
                </div>
            </div>
            <div class="form-group kringDebug">
                <label class="col-sm-4 control-label">{{Type}}</label>
                <div class="col-sm-8">
                    <input disabled class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="type"/>
                </div>
            </div>
            <div class="form-group kringDebug">
                <label class="col-sm-4 control-label">{{Equipements liés}}</label>
                <div class="col-sm-8">
                    <input disabled class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="linked_devices"/>
                </div>
            </div>
            <div class="form-group kringDebug">
                <label class="col-sm-4 control-label"></label>
                <div class="col-sm-8">
                  <label class="checkbox-inline"><input disabled type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="featured_motions_enabled" checked/>{{Featured: Mouvement}}</label>
                </div>
            </div>
            <div class="form-group kringDebug">
                <label class="col-sm-4 control-label"></label>
                <div class="col-sm-8">
                  <label class="checkbox-inline"><input disabled type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="featured_ring" checked/>{{Featured: Sonnette}}</label>
                </div>
            </div>
            <div class="form-group kringDebug">
                <label class="col-sm-4 control-label"></label>
                <div class="col-sm-8">
                  <label class="checkbox-inline"><input disabled type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="featured_volume" checked/>{{Featured: Volume}}</label>
                </div>
            </div>
            <div class="form-group kringDebug">
                <label class="col-sm-4 control-label"></label>
                <div class="col-sm-8">
                  <label class="checkbox-inline"><input disabled type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="featured_dnd" checked/>{{Featured: Ne pas déranger}}</label>
                </div>
            </div>
            <div class="form-group kringDebug">
                <label class="col-sm-4 control-label"></label>
                <div class="col-sm-8">
                  <label class="checkbox-inline"><input disabled type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="featured_play_sound" checked/>{{Featured: Jouer son}}</label>
                </div>
            </div>
            <div class="form-group kringDebug">
                <label class="col-sm-4 control-label"></label>
                <div class="col-sm-8">
                  <label class="checkbox-inline"><input disabled type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="featured_snapshots" checked/>{{Featured: Captures}}</label>
                </div>
            </div>
          </fieldset>
        </form>
      </div>
      <div class="col-lg-4">
        <form class="form-horizontal">
          <fieldset>
            <div class="form-group">
              <label class="control-label">{{Recharger les commandes}}</label>
            </div>
            <div class="form-group">
              <a class="btn btn-danger bt_kringCreateCmd" dataCmdType="all">
                <i class="fa fa-search"></i> {{Charger toutes les commandes}}
              </a>
            </div>
          </fieldset>
        </form>
      </div>
      <legend class="col-lg-12"><i class="fa fa-link"></i> {{Equipements liés}}</legend>
      <div class="eqLogicThumbnailContainer col-lg-8" id="linkedDevices">
        <?php
        foreach ($eqLogics as $eqLogic) {
        	$opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
        	echo '<div class="eqLogicDisplayCard cursor" data-id="' . $eqLogic->getDevice()->getVariable('id','') . '" data-eqLogic_id="' . $eqLogic->getId() . '" style="text-align: center; background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;' . $opacity . '" >';
        	echo '<img src="' . $eqLogic->getImage() . '" height="105" width="95" />';
        	echo "<br>";
        	echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;">' . $eqLogic->getHumanName(true, true) . '</span>';
        	echo '</div>';
        }
        ?>
      </div>
    </div>
    <div role="tabpanel" class="tab-pane" id="commandtab">
      <a class="btn btn-success btn-sm cmdAction pull-right" data-action="add" style="margin-top:5px;"><i class="fa fa-plus-circle"></i> {{Commandes}}</a><br/><br/>
      <table id="table_cmd" class="table table-bordered table-condensed">
        <thead>
          <tr>
            <th>{{Nom}}</th>
            <th>{{Type}}</th>
            <th>{{Paramètres}}</th>
            <th>{{Options}}</th>
            <th>{{Action}}</th>
          </tr>
        </thead>
        <tbody>
        </tbody>
      </table>
    </div>
    <div role="tabpanel" class="tab-pane" id="snaptab">
      <a class="btn btn-success btn-sm snapsAction pull-right" data-action="add" style="margin-top:5px;"><i class="fa fa-camera"></i> {{Nouvelle capture}}</a>
      <a class="btn btn-danger btn-sm snapsAction pull-right" data-action="deleteAll" style="margin-top:5px;"><i class="fa fa-delete"></i> {{Supprimer toutes}}</a>
      <a class="btn btn-default btn-sm snapsAction pull-right" data-action="refresh" style="margin-top:5px;"><i class="fa fa-refresh"></i> {{Rafraichir}}</a><br/><br/>
      <table id="table_snap" class="table table-bordered table-condensed">
        <thead>
          <tr>
            <th>{{Aperçu}}</th>
            <th>{{Equipement}}</th>
            <th>{{Date - Heure}}</th>
            <th>{{Evènement}}</th>
            <th>{{Action}}</th>
          </tr>
        </thead>
        <tbody>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include_file('desktop', 'kring', 'js', 'kring');?>
<?php include_file('core', 'plugin.template', 'js'); ?>
