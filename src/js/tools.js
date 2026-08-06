/* global jsAjaxServer, jsSaveTitle, jsWebPath, jsCancelTitle, jsAjaxUrl, dialog_buttons */

/* vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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
 *
 */

/***********/
/* Filters */
/***********/

export function showFilters(element, string, group_release) {
    if (group_release === true) {
        var link = $("#browse-options-link" + string);
        var hidelink = $("#browse-options-hidelink" + string);
        var content = $("#browse-options-content" + string);
    } else {
        var link = $(".browse-options-link");
        var hidelink = $(".browse-options-hidelink");
        var content = $(".browse-options-content");
    }
    link.hide();
    hidelink.show();
    content.show();
}

export function hideFilters(element, string, group_release) {
    if (group_release === true) {
        var link = $("#browse-options-link" + string);
        var hidelink = $("#browse-options-hidelink" + string);
        var content = $("#browse-options-content" + string);
    } else {
        var link = $(".browse-options-link");
        var hidelink = $(".browse-options-hidelink");
        var content = $(".browse-options-content");
    }
    link.show();
    hidelink.hide();
    content.hide();
}

/************************************************************/
/* Dialog selection to add song to an existing/new playlist */
/************************************************************/


// An href="#" link jumps the document to the top, which moves the page out from under the dialog being
// placed. Callers pass either the click event or the element, so only an event has a default to suppress.
function suppressDialogNavigation(source) {
    if (source && typeof source.preventDefault === "function") {
        source.preventDefault();
    }
}

// The element a popup dialog hangs off, given the click event, the element, or a jQuery wrapper of it.
function dialogAnchor(source) {
    if (!source) {
        return null;
    }

    if (source.nodeType) {
        return source;
    }

    // A context-menu callback has no event to offer, only the jQuery-wrapped trigger it fired from.
    if (source.jquery) {
        return source[0] || null;
    }

    return source.currentTarget || source.target || null;
}

// The broadcast button lives in the position:fixed web player, so a document coordinate for it is only valid
// at the scroll offset it was taken at. Place this one against the viewport so it tracks the button it hangs
// off; the playlist and share dialogs hang off page content and keep jQuery UI's own document placement.
function positionDialogNear(selector, source, offsetLeft) {
    var anchor = dialogAnchor(source);
    if (!anchor || typeof anchor.getBoundingClientRect !== "function") {
        return;
    }

    var frame = $(selector).closest(".ui-dialog");
    if (frame.length === 0) {
        return;
    }

    var rect = anchor.getBoundingClientRect();
    var left = Math.min(Math.max(rect.left + offsetLeft, 10), window.innerWidth - frame.outerWidth() - 10);
    var top = Math.min(Math.max(rect.top, 10), window.innerHeight - frame.outerHeight() - 10);

    frame.css({position: "fixed", top: Math.max(top, 10) + "px", left: Math.max(left, 10) + "px"});
}

var closeplaylist;
export function overlayclickclose() {
    if (closeplaylist) {
        $("#playlistdialog").dialog("close");
    }
    closeplaylist = 1;
}

export function showPlaylistDialog(e, item_type, item_ids, item_groups) {
    suppressDialogNavigation(e);
    $("#playlistdialog").dialog("close");

    var parent = window;
    parent.itemType = item_type;
    parent.contentUrl = jsAjaxServer + "/edit.server.php?action=show_edit_playlist&object_type=" + item_type + "&id=" + item_ids;
    if (item_groups) {
        parent.contentUrl += "&groups=" + encodeURIComponent(item_groups);
    }
    parent.editDialogId = "<div id=\"playlistdialog\"></div>";

    $(parent.editDialogId).dialog({
        modal: false,
        dialogClass: "playlistdialogstyle",
        resizable: false,
        draggable: false,
        width: 300,
        // auto, because the dialog now holds a playlist group and possibly a collection group
        height: "auto",
        maxHeight: 400,
        autoOpen: false,
        position: {
            my: "left+10 top",
            at: "left top",
            of: dialogAnchor(e),
            collision: "flip"
        },
        open() {
            closeplaylist = 1;
            $(document).on("click.playlistdialog", overlayclickclose);
            $(this).load(parent.contentUrl, function() {
                $("#playlistdialog").focus();
            });
        },
        focus() {
            closeplaylist = 0;
        },
        close(e) {
            $(document).off("click.playlistdialog");
            $(this).empty();
            $(this).dialog("destroy");
        }
    });

    $("#playlistdialog").dialog("open");
    closeplaylist = 0;
}

// append_item takes one type per call, so a selection spanning types is sent group by group. The first call
// carries any new-list name and reports the id it created, which the rest are then appended to.
// `idParam` names the key that id travels under, so the same stepping drives playlists and collections.
function appendPlaylistGroups(url, groups, id, idParam) {
    var idKey = idParam || "playlist_id";

    var pending = String(groups).split(";").filter(Boolean).map(function (group) {
        var parts = group.split(":");

        return {type: parts[0], ids: parts.slice(1).join(":")};
    });

    if (pending.length === 0) {
        ajaxPut(url, id);

        return;
    }

    // the url already carries the first group, so send it as-is and swap the type/ids in for the others
    var base = url.replace(/&item_type=[^&]*/, "").replace(/&item_id=[^&]*/, "");

    (function step(index, target) {
        if (index >= pending.length) {
            return;
        }

        var group = pending[index];
        // the id list stays a bare `1,2`: an encoded comma is refused with a 400 before it reaches php
        var call = target +
            "&item_type=" + group.type.replace(/[^a-z_]/g, "") +
            "&item_id=" + group.ids.replace(/[^0-9,]/g, "");

        $.ajax(call, {type: "post", dataType: "xml", success: function (data) {
            processContents(data);

            var created = $(data).find("content[div='" + idKey + "']").text();
            if (created && target.indexOf(idKey + "=") === -1) {
                target += "&" + idKey + "=" + created;
            }

            step(index + 1, target);
        }});
    }(0, base));
}

export function handlePlaylistAction(url, id, groups, idParam) {
    if (groups) {
        appendPlaylistGroups(url, groups, id, idParam);
    } else {
        ajaxPut(url, id);
    }

    $("#playlistdialog").dialog("close");
}

export function createNewPlaylist(title, url, id, groups, idParam) {
    var plname = window.prompt(title, "");
    if (plname !== null) {
        url += "&name=" + encodeURIComponent(plname);
        handlePlaylistAction(url, id, groups, idParam);
    }
}

var closeshare;
export function shoverlayclickclose(e) {
    if (closeshare) {
        $("#sharedialog").dialog("close");
    }
    closeshare = 1;
}

/************************************************************/
/* Dialog selection to start a broadcast */
/************************************************************/

var closebroadcasts;
export function showBroadcastsDialog(e) {
    suppressDialogNavigation(e);
    $("#broadcastsdialog").dialog("close");

    var parent = window;
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
            at: "left top",
            of: dialogAnchor(e),
            collision: "flip"
        },
        open() {
            closebroadcasts = 1;
            $(document).on("click.broadcastsdialog", broverlayclickclose);
            $(this).load(parent.contentUrl, function() {
                positionDialogNear("#broadcastsdialog", e, -180);
                $("#broadcastsdialog").focus();
            });
        },
        focus() {
            closebroadcasts = 0;
        },
        close(e) {
            $(document).off("click.broadcastsdialog");
            $(this).empty();
            $(this).dialog("destroy");
        }
    });

    $("#broadcastsdialog").dialog("open");
    positionDialogNear("#broadcastsdialog", e, -180);
    closebroadcasts = 0;
}

export function broverlayclickclose() {
    if (closebroadcasts) {
        $("#broadcastsdialog").dialog("close");
    }
    closebroadcasts = 1;
}

export function handleBroadcastAction(url, id) {
    ajaxPut(url, id);
    $("#broadcastsdialog").dialog("close");
}

/************************************************************/
/* Dialog selection to start a broadcast */
/************************************************************/

export function showShareDialog(e, object_type, object_id) {
    suppressDialogNavigation(e);
    $("#sharedialog").dialog("close");

    var parent = window;
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
            at: "left top",
            of: dialogAnchor(e),
            collision: "flip"
        },
        open() {
            closeshare = 1;
            $(document).on("click.sharedialog", shoverlayclickclose);
            $(this).load(parent.contentUrl, function() {
                $("#sharedialog").focus();
            });
        },
        focus() {
            closeshare = 0;
        },
        close(e) {
            $(document).off("click.sharedialog");
            $(this).empty();
            $(this).dialog("destroy");
        }
    });

    $("#sharedialog").dialog("open");
    closeshare = 0;
}

export function handleShareAction(url) {
    window.open(url);
    $("#sharedialog").dialog("close");
}

/***************************************************/
/* Edit modal dialog for artists, albums and songs */
/***************************************************/

var tag_choices;
var label_choices;

export function showEditDialog(edit_type, edit_id, edit_form_id, edit_title, refresh_row_prefix, argument_string) {
    var parent = window;
    parent.editFormId = "form#" + edit_form_id;
    parent.contentUrl = jsAjaxServer + "/edit.server.php?action=show_edit_object&id=" + edit_id + "&type=" + edit_type;
    parent.saveUrl = jsAjaxServer + "/edit.server.php?action=edit_object&id=" + edit_id + "&type=" + edit_type;
    parent.editDialogId = "<div id=\"editdialog\"></div>";
    parent.refreshRowPrefix = refresh_row_prefix;
    parent.editType = edit_type;
    parent.editId = edit_id;

    $.when($.ajax(jsAjaxServer + "/ajax.server.php?page=tag&action=get_tag_map&type=" + edit_type), $.ajax(jsAjaxServer + "/ajax.server.php?page=tag&action=get_labels&type=" + edit_type)).then(function( a1, a2 ) {

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

                        var url = jsAjaxServer + "/edit.server.php?action=refresh_updated&type=" + parent.editType + "&id=" + new_id + argument_string;
                        // Reload only table
                        $("#" + parent.refreshRowPrefix + parent.editId).load(url, function() {
                            // Update the current row identifier with new id
                            $("#" + parent.refreshRowPrefix + parent.editId).attr("id", parent.refreshRowPrefix + new_id);
                        });
                    } else {
                        loadContentPage(window.location.href);
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
            width: Math.min(666, $(window).width() - 20),
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

export function check_inline_song_edit(type, song) {
    var source = "#" + type + "_select_" + song;
    if ($(source + " option:selected").val() == -1) {
        $(source).fadeOut(600, function() {
            $(this).replaceWith("<input id=\"" + type + "_name\" type=\"text\" name=\"" + type + "_name\" value=\"New " + type + "\" onclick=\"this.select();\" />");
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

export function sortPlaylistRender() {
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

export function submitNewItemsOrder(itemId, tableid, rowPrefix, updateUrl, refreshAction) {
    var parent = window;
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

export function getPagePlaySettings() {
    var settings = "";
    var stg_subtitle = document.getElementById("play_setting_subtitle");
    if (typeof(stg_subtitle) !== "undefined" && stg_subtitle !== null) {
        if (stg_subtitle.value !== "") {
            settings += "&subtitle=" + stg_subtitle.value;
        }
    }

    return settings;
}

export function geolocate_user_callback(position) {
    var url = jsAjaxUrl + "?page=stats&action=geolocation&latitude=" + position.coords.latitude + "&longitude=" + position.coords.longitude;
    $.get(url);
}

export function geolocate_user() {
    if(navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(geolocate_user_callback);
    }
}

export function show_selected_license_link(license_select) {
    var license = $("#" + license_select + " option:selected");
    var link = license.attr("data-link");
    if (typeof(link) !== "undefined") {
        window.open(link);
    }
}
