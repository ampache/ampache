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

function showEditDialog(edit_type, edit_id, edit_form_id, edit_title, save_title, cancel_title) {
    var parent = this;
    parent.editFormId = 'form#' + edit_form_id;
    parent.contentUrl = jsAjaxUrl + '?action=show_edit_object&id=' + edit_id + '&type=' + edit_type;
    parent.saveUrl = jsAjaxUrl + '?action=edit_object&id=' + edit_id + '&type=' + edit_type;
    parent.editDialogId = '<div id="editdialog"></div>';
    
    parent.dialog_buttons = {};
    this.dialog_buttons[save_title] = function() {
        $.ajax({
            url     : parent.saveUrl,
            type    : 'POST',
            data    : $(parent.editFormId).serializeArray(),
            success : function(resp){
                $("#editdialog").dialog("close");
                // Need to replace current div instead of refreshing frame.
                window.location.reload();
            },
            error   : function(resp){
                $("#editdialog").dialog("close");
            }
         });
    }
    this.dialog_buttons[cancel_title] = function() {
        $("#editdialog").dialog("close");
    }
    
    $(parent.editDialogId).dialog({
        title: edit_title,
        modal: true,
        dialogClass: 'editdialogstyle',
        resizable: false,
        width: 600,
        autoOpen: false,
        open: function () {
            $(this).load(parent.contentUrl, function() {
                $(this).dialog('option', 'position', 'center');
                
                if ($('#edit_tags').length > 0) {
                    $("#edit_tags").tagit({
                        allowSpaces: true,
                        singleField: true,
                        singleFieldDelimiter: ','
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

function showAddTagSlideout(elm) {
    $(elm).show('slide', 500);
}

function closeAddTagSlideout(elm) {
    $(elm).hide('slide', 500);
}

function saveTag(id, type, path) {
    var tagName = $('#dialog_tag_item_tag_name_'+id).val();
    if (tagName != null || tagName != '') {
        ajaxPut(path + '/server/ajax.server.php?page=tag&action=add_tag_by_name&type=' + type + '&object_id=' + id + '&tag_name=' + tagName);
    }
    $('#dialog_tag_item_tag_name').val('');
    $('#dialog_tag_item_' + id).hide('slide', 500);
    $('#np_song_tags_' + id).append('<a href="javascript:void(0);" class="hover-remove tag_size2">' + tagName + '</a>')
}

function showAddTagDialog(id, type, path) {
    $('#dialog_tag_item').dialog({
        modal: true,
        buttons: [
            {
                title: "Tag",
                text: 'ok',
                click: function () {
                    var tagName = $('#dialog_tag_item_tag_name').val();
                    if (tagName != null || tagName != '') {
                        ajaxPut(path + '/server/ajax.server.php?page=tag&action=add_tag_by_name&type=' + type + '&object_id=' + id + '&tag_name=' + tagName);
                    }
                    $('#dialog_tag_item_tag_name').val('');
                    $(this).dialog('close');
                }
            }
        ]
    }).show();
}

