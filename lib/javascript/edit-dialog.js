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

function showEditDialog(edit_type, edit_id, edit_form_id, edit_title, edit_tag_choices) {
    var parent = this;
    parent.editFormId = 'form#' + edit_form_id;
    parent.contentUrl = jsAjaxShowEditUrl + '?action=show_edit_object&id=' + edit_id + '&type=' + edit_type;
    parent.saveUrl = jsAjaxUrl + '?action=edit_object&id=' + edit_id + '&type=' + edit_type;
    parent.editDialogId = '<div id="editdialog"></div>';
    
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
                $("#editdialog").dialog("close");
                // Need to replace current div instead of refreshing frame.
                window.location.reload();
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
