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
    $("a[rel^='prettyPhoto']").prettyPhoto({
        social_tools: false,
        deeplinking: false
    });
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
            // A .libitem_menu element states what it is through data-object-type/data-object-id.
            var object_type = item.attr('data-object-type');
            var object_id = item.attr('data-object-id');

            if (!object_type || !object_id) {
                var matched = /^(.+)_(\d+)$/.exec(item.attr('id') || '');
                if (matched === null) {
                    return;
                }

                object_type = matched[1];
                object_id = matched[2];
            }

            if (typeof action !== 'undefined' && action !== '') {
                ajaxPut(jsAjaxUrl + action + '&object_type=' + object_type + '&object_id=' + object_id);
            } else {
                // The context menu is torn down before this runs, so the dialog hangs off the .libitem_menu
                // element it was opened from -- that is still in the page and is where the user is looking.
                showPlaylistDialog(item, object_type, object_id);
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
var notificationShownAt = 0;
var notificationQueue = [];
// a message gets at least this long on screen before the next one can replace it, so a fast-arriving
// follow-up (e.g. a scan that finishes within a second of its first progress tick) can't erase one before it's read
var MIN_NOTIFICATION_DISPLAY_MS = 1000;
// backstop for a tab backgrounded through a long scan, where throttled timers let messages pile up faster
// than they can be shown; keeps the oldest (first) and newest (current status) and thins out the middle
var MAX_NOTIFICATION_QUEUE = 5;
// Applies a callback to every matching scope inside (or equal to) a node, now and for any node that
// arrives later: a browse or a preferences tab swapped in by ajax never saw the ready handler.
export function onScopeAdded(selector, apply) {
    function run(node) {
        var $node = $(node);

        $node.filter(selector).add($node.find(selector)).each(function () {
            apply($(this));
        });
    }

    $(function () {
        run(document.body);

        new MutationObserver(function (records) {
            records.forEach(function (record) {
                Array.prototype.forEach.call(record.addedNodes, function (node) {
                    if (node.nodeType === 1) {
                        run(node);
                    }
                });
            });
        }).observe(document.body, {childList: true, subtree: true});
    });
}

export function clearNotification() {
    notificationQueue = [];
    clearTimeout(notificationTimeout);
    notificationTimeout = null;
    notificationShownAt = 0;
    $("#notification").addClass("notification-out");
}

// Shows the next queued message, if any. Every message gets its turn in order -- a fast burst delays
// the ones behind it rather than letting a later arrival erase an earlier one that was never shown.
function advanceNotificationQueue() {
    clearTimeout(notificationTimeout);
    notificationTimeout = null;

    var next = notificationQueue.shift();
    if (!next) {
        notificationShownAt = 0;
        $("#notification").addClass("notification-out");
        return;
    }

    if ($("#webplayer").css("display") !== "block") {
        $("#notification").css("bottom", "20px");
    } else {
        $("#notification").css("bottom", "120px");
    }
    $("#notification-content").text(next.message);
    $("#notification").removeClass("notification-out");
    notificationShownAt = Date.now();

    if (next.timeout > 0) {
        notificationTimeout = setTimeout(advanceNotificationQueue, next.timeout);
    }
    // next.timeout <= 0: no timer here -- displayNotification() schedules one itself, at the minimum
    // display time, the moment something else is queued behind this message
}

// timeout <= 0 leaves the notification up until the user dismisses it by clicking it, unless another
// notification arrives behind it, in which case it still steps aside after its minimum display time
export function displayNotification(message, timeout) {
    if (!message) {
        clearNotification();
        return;
    }

    if (notificationQueue.length >= MAX_NOTIFICATION_QUEUE) {
        // drop from just behind the head so the first (already-queued) message is never the one sacrificed
        notificationQueue.splice(1, 1);
    }

    notificationQueue.push({message: message, timeout: timeout});

    var elapsed = Date.now() - notificationShownAt;
    if (!notificationShownAt || elapsed >= MIN_NOTIFICATION_DISPLAY_MS) {
        advanceNotificationQueue();
    } else if (notificationTimeout === null) {
        notificationTimeout = setTimeout(advanceNotificationQueue, MIN_NOTIFICATION_DISPLAY_MS - elapsed);
    }
}

// Filtering from the genre cloud swaps the listing further down the page, where the reader is not
// looking.
export function scrollToBrowse()
{
    var target = document.querySelector('[id^="browse_content"]');
    if (target) {
        target.scrollIntoView();
    }
}

export function initTabs()
{
    $(".default_hidden").hide();

    $("#tabs li").click(function() {
        var href = $(this).find("a").attr("href");
        // a tab pointing at another page is a link, not a panel to reveal
        if (typeof href === "undefined" || href.charAt(0) !== "#") {
            return;
        }

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
    $(field).disabled = $(field).disabled === false;
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
// `message` is plain text or a DOM/jQuery node; `options` takes `title` and `width`.
export function ampacheConfirm(message, options) {
    return new Promise(function (resolve) {
        var settings = options || {};
        var $dialog = $("#ampache-confirm-dialog");
        if ($dialog.length === 0) {
            $dialog = $("<div id='ampache-confirm-dialog'></div>").appendTo(document.body);
        }
        if (message instanceof $ || (message && message.nodeType)) {
            $dialog.empty().append(message);
        } else {
            $dialog.text((message === null || typeof message === "undefined") ? "" : String(message));
        }

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
        var titleLabel  = settings.title || ((typeof jsConfirmTitle !== "undefined") ? jsConfirmTitle : "Confirm");

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
            // the themes style dialogs by this class, and without one this fell back to jQuery UI's own
            dialogClass: "confirmdialogstyle",
            modal: true,
            resizable: false,
            draggable: false,
            width: settings.width || 400,
            close: function () {
                // fires for the X, the Escape key and our own close() call; the guard stops a double resolve
                settle(false);
            },
            buttons: buttons
        });
    });
}