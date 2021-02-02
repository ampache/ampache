/* global jsAjaxServer, jsSaveTitle, jsWebPath, jsCancelTitle, jsAjaxUrl, dialog_buttons */

/* vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/***********/
/* Filters */
/***********/

function showFilters(element) {
    var link = $(".browse-options-link");
    link.hide();
    var content = $(".browse-options-content");
    content.show();
}

/************************************************************/
/* Dialog selection to add song to an existing/new playlist */
/************************************************************/


var closeplaylist;
function overlayclickclose() {
    if (closeplaylist) {
        $("#playlistdialog").dialog("close");
    }
    closeplaylist = 1;
}

function showPlaylistDialog(e, item_type, item_ids) {
    $("#playlistdialog").dialog("close");

    var parent = this;
    parent.itemType = item_type;
    parent.contentUrl = jsAjaxServer + "/edit.server.php?action=show_edit_playlist&object_type=" + item_type + "&id=" + item_ids;
    parent.editDialogId = "<div id=\"playlistdialog\"></div>";

    $(parent.editDialogId).dialog({
        modal: false,
        dialogClass: "playlistdialogstyle",
        resizable: false,
        draggable: false,
        width: 300,
        height: 100,
        autoOpen: false,
        position: {
            my: "left+10 top",
            of: e
        },
        open() {
            closeplaylist = 1;
            $(document).bind("click", overlayclickclose);
            $(this).load(parent.contentUrl, function() {
                $("#playlistdialog").focus();
            });
        },
        focus() {
            closeplaylist = 0;
        },
        close(e) {
            $(document).unbind("click");
            $(this).empty();
            $(this).dialog("destroy");
        }
    });

    $("#playlistdialog").dialog("open");
    closeplaylist = 0;
}

function handlePlaylistAction(url, id) {
    ajaxPut(url, id);
    $("#playlistdialog").dialog("close");
}

function createNewPlaylist(title, url, id) {
    var plname = window.prompt(title, "");
    if (plname !== null) {
        url += "&name=" + plname;
        handlePlaylistAction(url, id);
    }
}

var closeshare;
function shoverlayclickclose(e) {
    if (closeshare) {
        $("#sharedialog").dialog("close");
    }
    closeshare = 1;
}

/************************************************************/
/* Dialog selection to start a broadcast */
/************************************************************/

var closebroadcasts;
function showBroadcastsDialog(e) {
    $("#broadcastsdialog").dialog("close");

    var parent = this;
    parent.contentUrl = jsAjaxServer + "/ajax.server.php?page=player&action=show_broadcasts";
    parent.editDialogId = "<div id=\"broadcastsdialog\"></div>";

    $(parent.editDialogId).dialog({
        modal: false,
        dialogClass: "broadcastsdialogstyle",
        resizable: false,
        draggable: false,
        width: 150,
        height: 70,
        autoOpen: false,
        position: {
            my: "left-180 top",
            of: e
        },
        open() {
            closebroadcasts = 1;
            $(document).bind("click", broverlayclickclose);
            $(this).load(parent.contentUrl, function() {
                $("#broadcastsdialog").focus();
            });
        },
        focus() {
            closebroadcasts = 0;
        },
        close(e) {
            $(document).unbind("click");
            $(this).empty();
            $(this).dialog("destroy");
        }
    });

    $("#broadcastsdialog").dialog("open");
    closebroadcasts = 0;
}

function broverlayclickclose() {
    if (closebroadcasts) {
        $("#broadcastsdialog").dialog("close");
    }
    closebroadcasts = 1;
}

function handleBroadcastAction(url, id) {
    ajaxPut(url, id);
    $("#broadcastsdialog").dialog("close");
}

/************************************************************/
/* Dialog selection to start a broadcast */
/************************************************************/

function showShareDialog(e, object_type, object_id) {
    $("#sharedialog").dialog("close");

    var parent = this;
    parent.contentUrl = jsAjaxServer + "/ajax.server.php?page=browse&action=get_share_links&object_type=" + object_type + "&object_id=" + object_id;
    parent.editDialogId = "<div id=\"sharedialog\"></div>";

    $(parent.editDialogId).dialog({
        modal: false,
        dialogClass: "sharedialogstyle",
        resizable: false,
        draggable: false,
        width: 200,
        height: 90,
        autoOpen: false,
        position: {
            my: "left+10 top",
            of: e
        },
        open() {
            closeshare = 1;
            $(document).bind("click", shoverlayclickclose);
            $(this).load(parent.contentUrl, function() {
                $("#sharedialog").focus();
            });
        },
        focus() {
            closeshare = 0;
        },
        close(e) {
            $(document).unbind("click");
            $(this).empty();
            $(this).dialog("destroy");
        }
    });

    $("#sharedialog").dialog("open");
    closeshare = 0;
}

function handleShareAction(url) {
    window.open(url);
    $("#sharedialog").dialog("close");
}

/***************************************************/
/* Edit modal dialog for artists, albums and songs */
/***************************************************/

var tag_choices;
var label_choices;

function showEditDialog(edit_type, edit_id, edit_form_id, edit_title, refresh_row_prefix) {
    var parent = this;
    parent.editFormId = "form#" + edit_form_id;
    parent.contentUrl = jsAjaxServer + "/edit.server.php?action=show_edit_object&id=" + edit_id + "&type=" + edit_type;
    parent.saveUrl = jsAjaxServer + "/edit.server.php?action=edit_object&id=" + edit_id + "&type=" + edit_type;
    parent.editDialogId = "<div id=\"editdialog\"></div>";
    parent.refreshRowPrefix = refresh_row_prefix;
    parent.editType = edit_type;
    parent.editId = edit_id;

    $.when($.ajax(jsAjaxServer + "/ajax.server.php?page=tag&action=get_tag_map"), $.ajax(jsAjaxServer + "/ajax.server.php?page=tag&action=get_labels")).then(function( a1, a2 ) {

        if(a1[2].status !== 200 || a2[2].status !== 200){
            displayNotification("Failed to open dialog", 5000);
        }

        var tag_choices;
        var label_choices;

        tag_choices = $(a1[0]).find("content").text();
        label_choices = $(a2[0]).find("content").text();

        var splitted = tag_choices.split(",");
        parent.editTagChoices = splitted.map($.trim);

        splitted = label_choices.split(",");
        parent.editLabelChoices = splitted.map($.trim);

        parent.dialog_buttons = {};
        parent.dialog_buttons[jsSaveTitle] = function () {
            $.ajax({
                url: parent.saveUrl,
                type: "POST",
                data: $(parent.editFormId).serializeArray(),
                success(resp) {
                    $("#editdialog").dialog("close");

                    if (parent.refreshRowPrefix !== "") {
                        var new_id = $.trim(resp.lastChild.textContent);

                        // resp should contain the new identifier, otherwise we take the same as the edited item
                        if (new_id == "") {
                            new_id = parent.editId;
                        }

                        var url = jsAjaxServer + "/edit.server.php?action=refresh_updated&type=" + parent.editType + "&id=" + new_id;
                        // Reload only table
                        $("#" + parent.refreshRowPrefix + parent.editId).load(url, function() {
                            // Update the current row identifier with new id
                            $("#" + parent.refreshRowPrefix + parent.editId).attr("id", parent.refreshRowPrefix + new_id);
                        });
                    } else {
                        var reloadp = window.location;
                        var hash = window.location.hash.substring(1);
                        if (hash && hash.indexOf(".php") > -1) {
                            reloadp = jsWebPath + "/" + hash;
                        }
                        loadContentPage(reloadp);
                    }
                },
                error(resp) {
                    $("#editdialog").dialog("close");
                }
            });
        };
        parent.dialog_buttons[jsCancelTitle] = function() {
            $("#editdialog").dialog("close");
        };

        $(parent.editDialogId).dialog({
            title: edit_title,
            modal: true,
            dialogClass: "editdialogstyle",
            resizable: false,
            width: 666,
            autoOpen: false,
            show: { effect: "fade", duration: 400 },
            open() {
                $(this).load(parent.contentUrl, function() {
                    if ($("#edit_tags").length > 0) {
                        $("#edit_tags").tagit({
                            allowSpaces: true,
                            singleField: true,
                            singleFieldDelimiter: ",",
                            availableTags: parent.editTagChoices
                        });
                    }
                    if ($("#edit_labels").length > 0) {
                        $("#edit_labels").tagit({
                            allowSpaces: true,
                            singleField: true,
                            singleFieldDelimiter: ",",
                            availableTags: parent.editLabelChoices
                        });
                    }
                });
            },
            close(e) {
                $(this).empty();
                $(this).dialog("destroy");
            },
            buttons: dialog_buttons
        });

        $("#editdialog").dialog("open");
        });
}

$(window).resize(function() {
    $("#editdialog").dialog("option", "position", {my: "center", at: "center", of: window});
});

function check_inline_song_edit(type, song) {
    var source = "#" + type + "_select_" + song;
    if ($(source + " option:selected").val() == -1) {
        $(source).fadeOut(600, function() {
            $(this).replaceWith("<input type=\"text\" name=\"" + type + "_name\" value=\"New " + type + "\" onclick=\"this.select();\" />");
        });
    }
    else {
        var change_to = $(source).val();
        $(source).val(change_to).prop("selected", true);
    }
}

/*********************/
/*   Sortable table  */
/*********************/

function sortPlaylistRender() {
    var eles = $("tbody[id^=\"sortableplaylist_\"]");
    if (eles !== null) {
        var len = eles.length;
        for (var i = 0; i < len; i++) {
            $("#" + eles[i].id).sortable({
                axis: "y",
                delay: 200
            });
        }
    }
}

$(document).ready(function () {
    sortPlaylistRender();
});

function submitNewItemsOrder(itemId, tableid, rowPrefix, updateUrl, refreshAction) {
    var parent = this;
    parent.itemId = itemId;
    parent.refreshAction = refreshAction;

    var table = document.getElementById(tableid);
    var rowLength = table.rows.length;
    var offset = 0;
    var finalOrder = "";

    if ($("#" + tableid).attr("data-offset")) {
        offset = $("#" + tableid).attr("data-offset");
    }

    for (var i = 0; i < rowLength; ++i) {
        var row = table.rows[i];
        if (row.id !== "") {
            var songid = row.id.replace(rowPrefix, "");
            finalOrder += songid + ";";
        }
    }

    if (finalOrder !== "") {
        $.ajax({
            url: updateUrl,
            type: "GET",
            async: false,
            data: "offset=" + offset + "&order=" + finalOrder,
            success(resp) {
                var url = jsAjaxServer + "/refresh_reordered.server.php?action=" + parent.refreshAction + "&id=" + parent.itemId;
                // Reload only table
                $("#reordered_list_" + parent.itemId).load(url, function () {
                    $("#sortableplaylist_" + parent.itemId).sortable({
                        axis: "y",
                        delay: 200
                    });
                });
            }
        });
    }
}

function getPagePlaySettings() {
    var settings = "";
    var stg_subtitle = document.getElementById("play_setting_subtitle");
    if (typeof(stg_subtitle) !== "undefined" && stg_subtitle !== null) {
        if (stg_subtitle.value !== "") {
            settings += "&subtitle=" + stg_subtitle.value;
        }
    }

    return settings;
}

function geolocate_user_callback(position) {
    var url = jsAjaxUrl + "?page=stats&action=geolocation&latitude=" + position.coords.latitude + "&longitude=" + position.coords.longitude;
    $.get(url);
}

function geolocate_user() {
    if(navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(geolocate_user_callback);
    } else {
        console.error("This browser does not support geolocation");
    }
}

function show_selected_license_link(license_select) {
    var license = $("#" + license_select + " option:selected");
    var link = license.attr("data-link");
    if (typeof(link) !== "undefined") {
        window.open(link);
    }
}
