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

$(document).ready(function () {
    initTabs();
    $.ajaxSetup({
        // Enable caching of AJAX responses, including script and jsonp
        cache: true
    });
    $("#notification").click(function() {
        clearNotification();
    });
});

$(function() {
    var rightmenu = $("#rightbar");
    var pos = rightmenu.offset();
    if (rightmenu.hasClass("rightbar-float")) {
        $(window).scroll(function() {
            var rightsubmenu = $("#rightbar .submenu");
            if ($(this).scrollTop() > (pos.top)) {
                rightmenu.addClass("fixedrightbar");
                rightsubmenu.addClass("fixedrightbarsubmenu");
            }
            else if ($(this).scrollTop() <= pos.top && rightmenu.hasClass("fixedrightbar")) {
                rightmenu.removeClass("fixedrightbar");
                rightsubmenu.removeClass("fixedrightbarsubmenu");
            }
            else {
                rightmenu.offset({ left: pos.left, top: pos.top });
            }
        });
    }
});

$(document).ready(function(){
    if (jsAmpConfigGeolocation) {
        geolocate_user();
    }

    if (jsAmpConfigLibitemContextmenu) {
        function libitem_action(item, action)
        {
            var iinfo = item.attr('id').split('_', 2);
            var object_type = iinfo[0];
            var object_id = iinfo[1];

            if (typeof action !== 'undefined' && action !== '') {
                ajaxPut(jsAjaxUrl + action + '&object_type=' + object_type + '&object_id=' + object_id);
            } else {
                showPlaylistDialog(this, object_type, object_id);
            }
        }

        $.contextMenu({
            selector: ".libitem_menu",
            items: {
                play: {name: "<?php echo $t_play; ?>", callback: function(key, opt){ libitem_action(opt.$trigger, '?page=stream&action=directplay'); }},
                play_next: {name: "<?php echo T_('Play next'); ?>", callback: function(key, opt){ libitem_action(opt.$trigger, '?page=stream&action=directplay&playnext=true'); }},
                play_last: {name: "<?php echo T_('Play last'); ?>", callback: function(key, opt){ libitem_action(opt.$trigger, '?page=stream&action=directplay&append=true'); }},
                add_tmp_playlist: {name: "<?php echo T_('Add to Temporary Playlist'); ?>", callback: function(key, opt){ libitem_action(opt.$trigger, '?action=basket'); }},
                add_playlist: {name: "<?php echo T_('Add to playlist'); ?>", callback: function(key, opt){ libitem_action(opt.$trigger, ''); }}
            }
        });
    }
});

var notificationTimeout = null;
export function clearNotification() {
    clearTimeout(notificationTimeout);
    notificationTimeout = null;
    $("#notification").addClass("notification-out");
}

export function displayNotification(message, timeout) {
    if (notificationTimeout !== null || !message) {
        clearNotification();
    }

    if (message) {
        if ($("#webplayer").css("display") !== "block") {
            $("#notification").css("bottom", "20px");
        } else {
            $("#notification").css("bottom", "120px");
        }
        $("#notification-content").html(message);
        $("#notification").removeClass("notification-out");
        notificationTimeout = setTimeout(function() {
            clearNotification();
        }, timeout);
    }
}

export function initTabs()
{
    $(".default_hidden").hide();

    $("#tabs li").click(function() {
        $("#tabs li").removeClass("tab_active");
        $(this).addClass("tab_active");
        $(".tab_content").hide();
        var selected_tab = $(this).find("a").attr("href");
        $(selected_tab).fadeIn();

        return false;
    });
}

// flipField
// Toggles the disabled property on the specifed field
export function flipField(field) {
    if ($(field).disabled === false) {
        $(field).disabled = true;
    }
    else {
        $(field).disabled = false;
    }
}

// updateText
// Changes the specified elements innards. Used for the catalog mojo fluff.
export function updateText(field, value) {
    $("#"+field).html(value);
}

// toggleVisible
// Toggles display type between block and none. Used for ajax loading div.
export function toggleVisible(element) {
    var target = $("#" + element);
    if (target.is(":visible")) {
        target.hide();
    } else {
        target.show();
    }
}

// delayRun
// This function delays the run of another function by X milliseconds
export function delayRun(element, time, method, page, source) {
    var function_string = method + "(\'" + page + "\',\'" + source + "\')";
    var action = function () { eval(function_string); };

    if (element.zid) {
        clearTimeout(element.zid);
    }

    element.zid = setTimeout(action, time);
}

// reloadUtil
// Reload our util frame
// IE issue fixed by Spocky, we have to use the iframe for Democratic Play &
// Localplay, which don't actually prompt for a new file
export function reloadUtil(target) {
    $("#util_iframe").prop("src", target);
}

export function reloadDivUtil(target) {
    var $util = $("#util_div");
    $.get(target, function (data, status, xhr) {
        var $response = $(data);
        $util.empty().append($response);
    });
}

// reloadRedirect
// Send them elsewhere
export function reloadRedirect(target) {
    window.location = target;
}

export function NavigateTo(url) {
    if (jsAmpConfigAjaxLoad) {
        window.location.hash = url.substring(jsWebPath.length + 1);
    } else {
        window.location.href = url;
    }
}

export function getCurrentPage() {
    if (jsAmpConfigAjaxLoad) {
        if (window.location.hash.length > 0) {
            var wpage = window.location.hash.substring(1);
            if (wpage !== 'prettyPhoto') {
                return btoa(wpage);
            } else {
                return "";
            }
        }

        return btoa(window.location.href.substring(jsWebPath.length + 1));
    } else {
        return btoa(window.location.href);
    }
}