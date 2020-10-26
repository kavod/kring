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

 function addCmdToTable(_cmd) {
     if (!isset(_cmd)) {
         var _cmd = {configuration: {}};
     }
     if (!isset(_cmd.configuration)) {
         _cmd.configuration = {};
     }
     var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
     tr += '<td>';
     tr += '<div class="row">';
     tr += '<div class="col-sm-6">';
     tr += '<a class="cmdAction btn btn-default btn-sm" data-l1key="chooseIcon"><i class="fa fa-flag"></i> {{Icône}}</a>';
     tr += '<span class="cmdAttr" data-l1key="display" data-l2key="icon" style="margin-left : 10px;"></span>';
     tr += '</div>';
     tr += '<div class="col-sm-6">';
     tr += '<input class="cmdAttr form-control input-sm" data-l1key="name">';
     tr += '</div>';
     tr += '</div>';
     tr += '<select class="cmdAttr form-control input-sm" data-l1key="value" style="display : none;margin-top : 5px;" title="{{La valeur de la commande vaut par défaut la commande}}">';
     tr += '<option value="">Aucune</option>';
     tr += '</select>';
     tr += '</td>';

     tr += '<td>';
     tr += '<input class="cmdAttr form-control input-sm" data-l1key="id" style="display : none;">';
     tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
     tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
     tr += '</td>';

     tr += '<td>';
     tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label></span> ';
     tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" checked/>{{Historiser}}</label></span> ';
     tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label></span> ';
     tr += '</td>';

     tr += '<td>';
     tr += '<input class="cmdAttr form-control input-sm" data-l1key="unite" placeholder="Unité" title="{{Unité}}">';
     tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="margin-top : 5px;"> ';
     tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="margin-top : 5px;">';
     tr += '</td>';

     tr += '<td>';
     if (is_numeric(_cmd.id)) {
         tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fa fa-cogs"></i></a> ';
         tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
     }
     tr += '<i class="fa fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>';
     tr += '</tr>';

     $('#table_cmd tbody').append(tr);
     $('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
     if (isset(_cmd.type)) {
         $('#table_cmd tbody tr:last .cmdAttr[data-l1key=type]').value(init(_cmd.type));
     }
     jeedom.cmd.changeType($('#table_cmd tbody tr:last'), init(_cmd.subType));
     var tr = $('#table_cmd tbody tr:last');
     jeedom.eqLogic.builSelectCmd({
         id: $('.eqLogicAttr[data-l1key=id]').value(),
         filter: {type: 'info'},
         error: function (error) {
             $('#div_alert').showAlert({message: error.message, level: 'danger'});
         },
         success: function (result) {
             tr.find('.cmdAttr[data-l1key=value]').append(result);
             tr.setValues(_cmd, '.cmdAttr');
             jeedom.cmd.changeType(tr, init(_cmd.subType));
         }
     });
 }
