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
                play: {name: jsPlay, callback: function(key, opt){ libitem_action(opt.$trigger, '?page=stream&action=directplay'); }},
                play_next: {name: jsPlayNext, callback: function(key, opt){ libitem_action(opt.$trigger, '?page=stream&action=directplay&playnext=true'); }},
                play_last: {name: jsPlayLast, callback: function(key, opt){ libitem_action(opt.$trigger, '?page=stream&action=directplay&append=true'); }},
                add_tmp_playlist: {name: jsAddTmpPlaylist, callback: function(key, opt){ libitem_action(opt.$trigger, '?action=basket'); }},
                add_playlist: {name: jsAddPlaylist, callback: function(key, opt){ libitem_action(opt.$trigger, ''); }}
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
    // method is a global function name (e.g. "ajaxState"); look it up instead of eval-ing a code string
    var action = function () {
        if (typeof window[method] === "function") {
            window[method](page, source);
        }
    };

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

// ampacheUrl
// Resolve a link against the current document and return it as a URL when it points inside this
// Ampache install, otherwise null. The trailing "/" on the base normalises both install shapes:
// https://host -> base pathname "/", https://host/music -> "/music/". A real origin comparison and
// a real prefix test, rather than a substring search and positional math on jsWebPath.length.
export function ampacheUrl(link) {
    var target, base;
    try {
        target = new URL(link, window.location.href);
        base   = new URL(jsWebPath + "/", window.location.href);
    } catch (e) {
        return null;
    }
    if (target.origin !== base.origin) {
        return null;
    }
    if (target.pathname.indexOf(base.pathname) !== 0) {
        return null;
    }

    return target;
}

// ampachePagePath
// The page part of an internal url relative to the Ampache web root, e.g. "browse.php?action=album".
// This is the value the old code read out of location.hash, so consumers keep working unchanged.
export function ampachePagePath(link) {
    var target = ampacheUrl(link || window.location.href);
    if (!target) {
        return "";
    }
    var base = new URL(jsWebPath + "/", window.location.href);

    return target.pathname.substring(base.pathname.length) + target.search;
}

// navigateToUrl
// Client side navigation. Swaps the page content and puts the real, server routable url in the
// address bar, so links can be read, shared and debugged. External urls are handed to the browser.
export function navigateToUrl(url) {
    var target = ampacheUrl(url);
    if (!target) {
        window.location.href = url;

        return;
    }
    // already here; re-fetching would throw away the page we are looking at for an identical one
    if (target.href === window.location.href) {
        return;
    }
    history.pushState(null, "", target.href);
    loadContentPage(target.href);
}

export function NavigateTo(url) {
    navigateToUrl(url);
}

export function appendDeleteBurl(link) {
    if (!/\/(song|albums|artists|video|labels|podcast_episode|podcast)\.php\?[^#]*\baction=delete\b/.test(link) || /[?&]burl=/.test(link)) {
        return link;
    }
    var origin = getCurrentPage();
    if (!origin) {
        return link;
    }

    return link + "&burl=" + encodeURIComponent(origin);
}

export function getCurrentPage() {
    // the lightbox writes #prettyPhoto into the hash; that is not a page to come back to
    if (window.location.hash === "#prettyPhoto") {
        return "";
    }

    return btoa(window.location.href);
}

// ampacheConfirm
// Non-blocking replacement for window.confirm(). The native confirm() freezes the event loop while
// it is open and, in some browsers (notably Firefox), pauses <audio>/<video> playback for the
// duration of the dialog, interrupting the web player. This shows a themed jQuery UI dialog instead
// and returns a Promise that resolves true (accepted) or false (cancelled/dismissed).
export function ampacheConfirm(message) {
    return new Promise(function (resolve) {
        var $dialog = $("#ampache-confirm-dialog");
        if ($dialog.length === 0) {
            $dialog = $("<div id='ampache-confirm-dialog'></div>").appendTo(document.body);
        }
        $dialog.text((message === null || typeof message === "undefined") ? "" : String(message));

        var settled = false;
        function settle(result) {
            if (settled) {
                return;
            }
            settled = true;
            resolve(result);
            if ($dialog.hasClass("ui-dialog-content")) {
                $dialog.dialog("close");
            }
        }

        var okLabel     = (typeof jsConfirmOkTitle !== "undefined") ? jsConfirmOkTitle : "OK";
        var cancelLabel = (typeof jsCancelTitle !== "undefined") ? jsCancelTitle : "Cancel";
        var titleLabel  = (typeof jsConfirmTitle !== "undefined") ? jsConfirmTitle : "Confirm";

        // Object form keeps the button order (accept first, then cancel).
        var buttons = {};
        buttons[okLabel] = function () {
            settle(true);
        };
        buttons[cancelLabel] = function () {
            settle(false);
        };

        $dialog.dialog({
            title: titleLabel,
            modal: true,
            resizable: false,
            draggable: false,
            width: 400,
            close: function () {
                // fires for the X, the Escape key and our own close() call; the guard stops a double resolve
                settle(false);
            },
            buttons: buttons
        });
    });
}