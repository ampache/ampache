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
    var contentUrl = jsAjaxUrl + '?action=show_edit_object&id=' + edit_id + '&type=' + edit_type;
    var saveUrl = jsAjaxUrl + '?action=edit_object&id=' + edit_id + '&type=' + edit_type;
    var cancelUrl = jsAjaxUrl + '?action=cancel_edit_object&id=' + edit_id + '&type=' + edit_type;   //No useful I think
    var editDialogId = '<div id="editdialog"></div>';
    var parent = this;
    
    var dialog_buttons = {};
    dialog_buttons[save_title] = function() {
        $(parent.edit_form_id).submit();
        ajaxPut(parent.saveUrl);
        $("#editdialog").dialog("close");
    }
    dialog_buttons[cancel_title] = function() {
        $("#editdialog").dialog("close");
    }
    
    $(editDialogId).dialog({
        title: edit_title,
        modal: true,
        dialogClass: 'editdialogstyle',
        resizable: false,
        width: 600,
        autoOpen: false,
        open: function () {
            $(this).load(contentUrl, function() {
                $(this).dialog('option', 'position', 'center');
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

