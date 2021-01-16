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
 $('#bt_savePluginConfig1').click(function() {
    $('#bt_savePluginConfig').click();
 });

 $('#bt_sendPassword').on('click', function () {
   var cmdType = $(this).attr("dataCmdType");
   var dialog_title = '{{Code de vérification}}';
   var dialog_message = '';
   dialog_message  = '<form class="form-horizontal" onsubmit="return false;">';
   dialog_message += '  <fieldset>';
   dialog_message += '    <label class="col-lg-4 control-label">{{Nom d\'utilisateur Ring.com (email)}}</label>';
   dialog_message += '    <div class="col-lg-2">';
   dialog_message += '      <input class="configKey form-control" name="username" />';
   dialog_message += '    </div>';
   dialog_message += '    <label class="col-lg-4 control-label">{{Mot de passe Ring.com}}</label>';
   dialog_message += '    <div class="col-lg-2">';
   dialog_message += '      <input type="password" class="configKey form-control" name="password" />';
   dialog_message += '    </div>';
   dialog_message += '  </fieldset>';
   dialog_message += '</form>';
   bootbox.dialog({
     title: dialog_title,
     message: dialog_message,
     buttons: {
       "{{Annuler}}": {
         className: "btn-danger",
         callback: function () {}
       },
       success: {
         label: "Démarrer",
         className: "btn-success",
         callback: function () {
           username = $("input[name='username']").val();
           password = $("input[name='password']").val();
           askCode(username,password);
         }
       }
     },
   });
 });

 $(document).ready(function() {
   $(".eqLogicAttr[data-l1key='id']").change(function(){
     $("#eqLogic_img").attr('src',$(".eqLogicDisplayCard[data-eqLogic_id='"+$(this).val()+"'] img").first().attr('src'));
   });
   $(".eqLogicAttr[data-l1key='logicalId']").change(function(){
     logicalId = $(this).val();
     if ($(this).val()!='')
     {
       $('.snapAction').on('click', function() {
         if ($(this).attr('data-action') == 'add')
         {
           $.ajax({// fonction permettant de faire de l'ajax
               type: "POST", // methode de transmission des données au fichier php
               url: "plugins/kring/core/ajax/kring.ajax.php", // url du fichier php
               data: {
                   action: "newSnapshot",
                   logicalId: logicalId
               },
               dataType: 'json',
               error: function (request, status, error) {
                   handleAjaxError(request, status, error);
               },
               success: function (data) { // si l'appel a bien fonctionné
                 if (data.state != 'ok') {
                     $('#div_alert').showAlert({message: data.result, level: 'danger'});
                     return;
                 }
                 refreshSnapshots(logicalId);
               }
           });
         }
       });
       refreshSnapshots(logicalId);
     }
   });
   $(".eqLogicAttr[data-l2key='linked_devices']").change(function(){
     $(".eqLogicThumbnailContainer#linkedDevices .eqLogicDisplayCard").hide();
     var linked_devices = $(this).val();
     if (linked_devices!='')
     {
       var obj_dev = jQuery.parseJSON( linked_devices );
       obj_dev.forEach(device => $(".eqLogicThumbnailContainer#linkedDevices " +
        ".eqLogicDisplayCard[data-id='"+device+"']").show());
     }
   });
 });

 function refreshSnapshots(logicalId) {
   if (logicalId == 'undefined')
    logicalId = $(".eqLogicAttr[data-l1key='logicalId']").val();
   $('#table_snap tbody').empty();
   getSnapshotList(logicalId);
 }

function getSnapshotList(logicalId) {
  $.ajax({// fonction permettant de faire de l'ajax
      type: "POST", // methode de transmission des données au fichier php
      url: "plugins/kring/core/ajax/kring.ajax.php", // url du fichier php
      data: {
          action: "getSnapshotList",
          logicalId: logicalId
      },
      dataType: 'json',
      error: function (request, status, error) {
          handleAjaxError(request, status, error);
      },
      success: function (data) { // si l'appel a bien fonctionné
          if (data.state != 'ok') {
              $('#div_alert').showAlert({message: data.result, level: 'danger'});
              return;
          }
          json = JSON.parse(data.result);
          json.forEach(function(snap) {
            addSnapToTable(snap);
          });
          $('.kringThumb').on('click', function() {
            var file_path = $(this).attr('data-uri');
            var datetime = $(this).attr('data-datetime');
             $('#md_modal2').dialog({
               title: datetime
             });
             $('#md_modal2').load('index.php?v=d&modal=widget.modal&data='+file_path+'&type=image').dialog('open');
           });

           $('.snapAction').on('click', function() {
             if ($(this).attr('data-action') == 'remove')
             {
               tr = $(this).closest("tr");
               logicalId = $(".eqLogicAttr[data-l1key='logicalId']").val();
               timestamp = tr.attr('data-timestamp');
               deleteSnapshot(logicalId,timestamp);
               tr.remove();
             }
           });
        }
  });
}

function deleteSnapshot(logicalId,timestamp) {
  $.ajax({
      type: "POST",
      url: "plugins/kring/core/ajax/kring.ajax.php",
      data: {
          action: "deleteSnapshot",
          logicalId: logicalId,
          timestamp: timestamp
      },
      dataType: 'json',
      error: function (request, status, error) {
          handleAjaxError(request, status, error);
      },
      success: function (data) {
        if (data.state != 'ok') {
            $('#div_alert').showAlert({message: data.result, level: 'danger'});
            return;
        }
      }
  });
}

function askCode(username,password) {
     $.ajax({// fonction permettant de faire de l'ajax
         type: "POST", // methode de transmission des données au fichier php
         url: "plugins/kring/core/ajax/kring.ajax.php", // url du fichier php
         data: {
             action: "askCode",
             username: username,
             password: password
         },
         dataType: 'json',
         error: function (request, status, error) {
             handleAjaxError(request, status, error);
         },
         success: function (data) { // si l'appel a bien fonctionné
             if (data.state != 'ok') {
                 $('#div_alert').showAlert({message: data.result, level: 'danger'});
                 return;
             }
             //data.result
             var cmdType = $(this).attr("dataCmdType");
             var dialog_title = '{{Code de vérification}}';
             var dialog_message = '<form class="form-horizontal onsubmit="return false;"> ';
             dialog_message += '<label class="control-label" > {{Un code de vérification a été envoyé sur :}} ' + data.result + ' </label> ' +
             '<div> ' +
             '<input type="text" name="verif_code" id="verif_code" value="" /> ' +
             '</div> ';
             dialog_message += '</form>';
             bootbox.dialog({
               title: dialog_title,
               message: dialog_message,
               buttons: {
                 "{{Annuler}}": {
                   className: "btn-danger",
                   callback: function () {}
                 },
                 success: {
                   label: "Démarrer",
                   className: "btn-success",
                   callback: function () {
                     verif_code = $("input[name='verif_code']").val();
                     kringAuth(verif_code);
                   }
                 }
               },
             });
             // $('#div_alert').showAlert({message: "{{Synchronisation réussie}}. "+data.result.toString()+" {{équipement(s) trouvé(s)}}. {{Merci de raffraichir la page}}", level: 'success'});
             // var vars = getUrlVars();
             // var url = 'index.php?';
             // for (var i in vars) {
             //   if (i != 'id' && i != 'saveSuccessFull' && i != 'removeSuccessFull') {
             //     url += i + '=' + vars[i].replace('#', '') + '&';
             //   }
             // }
             // url += 'syncedDevices=' + data.result.toString();
             // loadPage(url);
         }
     });
 };

 function kringAuth(verif_code) {
   $.ajax({// fonction permettant de faire de l'ajax
       type: "POST", // methode de transmission des données au fichier php
       url: "plugins/kring/core/ajax/kring.ajax.php", // url du fichier php
       data: {
           action: "authCode",
           verif_code: verif_code
       },
       dataType: 'json',
       error: function (request, status, error) {
           handleAjaxError(request, status, error);
       },
       success: function (data) { // si l'appel a bien fonctionné
           if (data.state != 'ok') {
               $('#div_alert').showAlert({message: data.result, level: 'danger'});
               return;
           }
           $('#div_alert').showAlert({message: "{{Authentification réussie}}", level: 'success'});
           return;
         }
       });
 }
 $('#btSync').on('click', function () {
     $.ajax({// fonction permettant de faire de l'ajax
         type: "POST", // methode de transmission des données au fichier php
         url: "plugins/kring/core/ajax/kring.ajax.php", // url du fichier php
         data: {
             action: "findEquipments",
         },
         dataType: 'json',
         error: function (request, status, error) {
             handleAjaxError(request, status, error);
         },
         success: function (data) { // si l'appel a bien fonctionné
             if (data.state != 'ok') {
                 $('#div_alert').showAlert({message: data.result, level: 'danger'});
                 return;
             }
             $('#div_alert').showAlert({message: "{{Synchronisation réussie}}. "+data.result.toString()+" {{équipement(s) trouvé(s)}}. {{Merci de raffraichir la page}}", level: 'success'});
             var vars = getUrlVars();
             var url = 'index.php?';
             for (var i in vars) {
               if (i != 'id' && i != 'saveSuccessFull' && i != 'removeSuccessFull') {
                 url += i + '=' + vars[i].replace('#', '') + '&';
               }
             }
             url += 'syncedDevices=' + data.result.toString();
             loadPage(url);
         }
     });
 });

 function kringCreateCmd(cmdType,force=0)
 {
   $.ajax({
       type: "POST",
       url: "plugins/kring/core/ajax/kring.ajax.php",
       data: {
           action: "createCmd",
           id: $('.eqLogicAttr[data-l1key=id]').value(),
           createcommand: force,
           cmdType: cmdType
       },
       dataType: 'json',
       global: false,
       error: function (request, status, error) {
           handleAjaxError(request, status, error);
       },
       success: function (data) {
           if (data.state != 'ok') {
               $('#div_alert').showAlert({message: data.result, level: 'danger'});
               return;
           }
           $('#div_alert').showAlert({message: '{{Opération réalisée avec succès}}', level: 'success'});
           $('.li_eqLogic[data-eqLogic_id=' + $('.eqLogicAttr[data-l1key=id]').value() + ']').click();
       }
   });
 }

 $('.bt_kringCreateCmd').on('click', function () {
   var cmdType = $(this).attr("dataCmdType");
   var dialog_title = '{{Recharge configuration}}';
   var dialog_message = '<form class="form-horizontal onsubmit="return false;"> ';
   dialog_title = '{{Recharger la configuration}}';
   dialog_message += '<label class="control-label" > {{Sélectionner le mode de rechargement de la configuration}} </label> ' +
   '<div> <div class="radio"> <label > ' +
   '<input type="radio" name="command" id="command-0" value="0" checked="checked"> {{Sans recréer les commandes mais en créeant les manquantes}} </label> ' +
   '</div><div class="radio"> <label > ' +
   '<input type="radio" name="command" id="command-1" value="1"> {{En recréant les commandes}}</label> ' +
   '</div> ' +
   '</div><br>' +
   '<label class="lbl lbl-warning" for="name">{{Attention, "en recréant les commandes" va supprimer les commandes existantes.}}</label> ';
   dialog_message += '</form>';
   bootbox.dialog({
     title: dialog_title,
     message: dialog_message,
     buttons: {
       "{{Annuler}}": {
         className: "btn-danger",
         callback: function () {}
       },
       success: {
         label: "Démarrer",
         className: "btn-success",
         callback: function () {
           createCommand = $("input[name='command']:checked").val();
           if (createCommand == "1")
           {
             bootbox.confirm('{{Etes-vous sûr de vouloir récréer les commandes ? Cela va supprimer les commandes existantes}}', function (result) {
               if (result) {
                 kringCreateCmd(cmdType,force=1);
               }
             });
           } else
           {
             kringCreateCmd(cmdType,force=0);
           }
         }
       }
     },
   });
 });

 function addSnapToTable(_snap)
 {
   let datetime = new Date(parseInt(_snap.timestamp));
   var tr = '<tr data-timestamp="' + init(_snap.timestamp) + '">';
   tr += '<td>';
   tr += '<img src="' + _snap.imageData + '" alt="" class="kringThumb" data-uri="'+_snap.file_path+'" data-datetime="' + datetime.toString() + '"/>';
   tr += '</td>';

   tr += '<td>';
   tr += '<span>' + _snap.deviceName + '</span>';
   tr += '</td>';

   tr += '<td>';
   tr += '<span>' + datetime.toString() + '</span>';
   tr += '</td>';

   tr += '<td>';
   tr += '<span>' + _snap.event + '</span>';
   tr += '</td>';

   tr += '<td>';
   tr += '<i class="fa fa-minus-circle pull-right snapAction cursor" data-action="remove"></i></td>';
   tr += '</td>';

   tr += '</tr>';

   $('#table_snap tbody').append(tr);
   $('#table_snap tbody tr:last').setValues(_snap, '.snapAttr');
 }

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
