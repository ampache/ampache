// vim:set softtabstop=4 shiftwidth=4 expandtab:
//
// Copyright 2001 - 2013 Ampache.org
// All rights reserved.
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License v2
// as published by the Free Software Foundation.
// 
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//

/***********/
/* Filters */
/***********/

function showFilters(element) {
    var link = $('.browse-options-link');
    link.hide();
    var content = $('.browse-options-content');
    content.show();
}

/************************************************************/
/* Dialog selection to add song to an existing/new playlist */
/************************************************************/

var closeplaylist;
function showPlaylistDialog(e, item_type, item_id) {
    $("#playlistdialog").dialog("close");

    var parent = this;
    parent.itemType = item_type;
    parent.itemId = item_id;
    parent.contentUrl = jsAjaxServer + '/show_edit_playlist.server.php?action=show_edit_object&item_type=' + item_type + '&item_id=' + item_id;
    parent.editDialogId = '<div id="playlistdialog"></div>';

    $(parent.editDialogId).dialog({
        modal: false,
        dialogClass: 'playlistdialogstyle',
        resizable: false,
        draggable: false,
        width: 300,
        height: 100,
        autoOpen: false,
        open: function () {
            closeplaylist = 1;
            $(document).bind('click', overlayclickclose);
            $(this).load(parent.contentUrl, function() {
                $('#playlistdialog').focus();
            });
        },
        focus: function() {
            closeplaylist = 0;
        },
        close: function (e) {
            $(document).unbind('click');
            $(this).empty();
            $(this).dialog("destroy");
        }
    });
    
    $("#playlistdialog").dialog("option", "position", [e.clientX + 10, e.clientY]);
    $("#playlistdialog").dialog("open");
    closeplaylist = 0;
}

function overlayclickclose() {
    if (closeplaylist) {
        $("#playlistdialog").dialog("close");
    }
    closeplaylist = 1;
}

function handlePlaylistAction(url, id) {
    ajaxPut(url, id);
    $("#playlistdialog").dialog("close");
}

/***************************************************/
/* Edit modal dialog for artists, albums and songs */
/***************************************************/

function showEditDialog(edit_type, edit_id, edit_form_id, edit_title, edit_tag_choices, refresh_row_prefix, refresh_action) {
    var parent = this;
    parent.editFormId = 'form#' + edit_form_id;
    parent.contentUrl = jsAjaxServer + '/show_edit.server.php?action=show_edit_object&id=' + edit_id + '&type=' + edit_type;
    parent.saveUrl = jsAjaxUrl + '?action=edit_object&id=' + edit_id + '&type=' + edit_type;
    parent.editDialogId = '<div id="editdialog"></div>';
    parent.refreshRowPrefix = refresh_row_prefix;
    parent.refreshAction = refresh_action;
    parent.editId = edit_id;
    
    // Convert choices string ("tag0,tag1,tag2,...") to choices array
    parent.editTagChoices = new Array();
    if (edit_tag_choices != null && edit_tag_choices != '') {
        var splitted = edit_tag_choices.split(',');
        var i;
        for (i = 0; i < splitted.length; ++i) {
            parent.editTagChoices.push($.trim(splitted[i]));
        }
    }
    
    parent.dialog_buttons = {};
    this.dialog_buttons[jsSaveTitle] = function() {
        $.ajax({
            url     : parent.saveUrl,
            type    : 'POST',
            data    : $(parent.editFormId).serializeArray(),
            success : function(resp){
                var new_id = $.trim(resp.lastChild.textContent);
                $("#editdialog").dialog("close");
                
                // resp should contain the new identifier, otherwise we take the same as the edited item
                if (new_id == '') {
                    new_id = parent.editId;
                }
                
                var url = jsAjaxServer + '/refresh_updated.server.php?action=' + parent.refreshAction + '&id=' + new_id;
                // Reload only table
                $('#' + parent.refreshRowPrefix + parent.editId).load(url, function() {
                    // Update the current row identifier with new id
                    $('#' + parent.refreshRowPrefix + parent.editId).attr("id", parent.refreshRowPrefix + new_id);
                });
            },
            error   : function(resp){
                $("#editdialog").dialog("close");
            }
         });
    }
    this.dialog_buttons[jsCancelTitle] = function() {
        $("#editdialog").dialog("close");
    }
    
    $(parent.editDialogId).dialog({
        title: edit_title,
        modal: true,
        dialogClass: 'editdialogstyle',
        resizable: false,
        width: 600,
        autoOpen: false,
        show: { effect: "fade", duration: 400 },
        open: function () {
            $(this).load(parent.contentUrl, function() {
                $(this).dialog('option', 'position', 'center');
                
                if ($('#edit_tags').length > 0) {
                    $("#edit_tags").tagit({
                        allowSpaces: true,
                        singleField: true,
                        singleFieldDelimiter: ',',
                        availableTags: parent.editTagChoices
                    });
                }
            });
        },
        close: function (e) {
            $(this).empty();
            $(this).dialog("destroy");
        },
        buttons: dialog_buttons
    });
    
    $("#editdialog").dialog("open");
}

$(window).resize(function() {
    $("#editdialog").dialog("option", "position", ['center', 'center']);
});

function check_inline_song_edit(type, song) {
    var source = '#' + type + '_select_' + song;
    if ($(source + ' option:selected').val() == -1) {
        $(source).replaceWith('<input type="text" name="' + type + '_name" value="New ' + type + '" onclick="this.select();" />');
    }
}

/*********************/
/*   Sortable table  */
/*********************/

$(document).ready(function () {
    $('#sortableplaylist').sortable({
        axis: 'y',
        delay: 200
    });
});

function submitNewItemsOrder(itemId, tableid, rowPrefix, updateUrl, refreshAction) {
    var parent = this;
    parent.itemId = itemId;
    parent.refreshAction = refreshAction;

    var table = document.getElementById(tableid);
    var rowLength = table.rows.length;
    var finalOrder = '';
    
    for (var i = 0; i < rowLength; ++i) {
        var row = table.rows[i];
        if (row.id != '') {
            var songid = row.id.replace(rowPrefix, '');
            finalOrder += songid + ";"
        }
    }
    
    if (finalOrder != '') {
        $.ajax({
            url  : updateUrl,
            type : 'GET',
            data : 'order=' + finalOrder,
            success : function(resp){
                var url = jsAjaxServer + '/refresh_reordered.server.php?action=' + parent.refreshAction + '&id=' + parent.itemId;
                // Reload only table
                $('#reordered_list').load(url, function() {
                    $('#sortableplaylist').sortable({
                        axis: 'y',
                        delay: 200
                    });
                });
            }
        });
    }
}
