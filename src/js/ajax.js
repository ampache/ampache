
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

function hasScriptableUrlScheme(url) {
    var scheme = String(url).replace(/[\u0000-\u0020\u00a0\u1680\u2000-\u200d\u2028\u2029\u202f\u205f\u3000\ufeff]/g, "");

    return /^(?:javascript|data|vbscript):/i.test(scheme);
}

// Delegated click handler for Ajax buttons rendered by Ajax::button()
// with data-* attributes. This replaces the per-button inline <script>
// observers and replays exactly the same logic: update_action() first,
// then optional confirm(), then ajaxPost()/ajaxPut() with the element
// id as source. Delegating on document also covers buttons injected
// dynamically through AJAX content updates.
$(document).on("click", "a[data-ajax='1']", function (event) {
    event.preventDefault();

    var $link = $(this);
    var url = $link.attr("data-ajax-url");
    var post = $link.attr("data-ajax-post");
    var confirmText = $link.attr("data-ajax-confirm");
    var source = this.id || "";

    // Keep the historical order: update_action() runs before confirm().
    // Use the window function so template overrides (html5 player) apply.
    window.update_action();

    function run() {
        if (post) {
            ajaxPost(url, post, source);
        } else {
            ajaxPut(url, source);
        }
    }

    // Non-blocking confirm so playback is not paused while the dialog is open.
    if (confirmText) {
        window.ampacheConfirm(confirmText).then(function (ok) {
            if (ok) {
                run();
            }
        });

        return;
    }

    run();
});

// Delegated confirm for plain links carrying data-confirm (replacing the old inline
// onclick="return confirm(...)"). We keep the link's original href untouched: on accept we replay
// a native click so whatever the href does (NavigateTo(...), a real navigation, ...) runs exactly
// as before, just without the blocking native confirm() that pauses the web player.
$(document).on("click", "a[data-confirm]", function (event) {
    var element = this;
    var $link = $(element);

    // Second pass: our own replayed click. Let it proceed to the browser default untouched.
    if ($link.data("ampConfirmed")) {
        $link.removeData("ampConfirmed");

        return;
    }

    event.preventDefault();
    window.ampacheConfirm($link.attr("data-confirm")).then(function (ok) {
        if (!ok) {
            return;
        }
        $link.data("ampConfirmed", true);
        element.click();
    });
});

// Some cutesy flashing thing while we run
$(document).ajaxSend(function () {
    $("#ajax-loading").show();
});
$(document).ajaxComplete(function () {
    $("#ajax-loading").hide();
});

// The page currently rendered into #content, as path+search. Used to tell a real back/forward move
// apart from a fragment-only one: the lightbox writes #prettyPhoto into the url, and going back over
// that must not re-fetch the page we are already looking at (the old hashchange handler ignored it).
var loadedPage = window.location.pathname + window.location.search;

$(function() {

    $("body").delegate("a", "click", function() {
        var link = $(this).attr("href");
        if (typeof link !== "undefined" && link !== "" && !hasScriptableUrlScheme(link) && link !== "#" && typeof link !== "undefined" && typeof $(this).attr("onclick") === "undefined" && typeof $(this).attr("data-confirm") === "undefined" && !$(this).hasClass("nohtml") && $(this).attr("target") !== "_blank") {
            if ($(this).attr("rel") !== "prettyPhoto") {
                // Ajax load Ampache pages only
                if (ampacheUrl(link)) {
                    navigateToUrl(link);
                    return false;
                }
            } else {
                window.location.hash = $(this).attr("rel");
                return false;
            }
        }
    });

    $("body").delegate("form", "submit", function(e) {
        // The login and export forms, and anything aimed at its own target, must navigate normally
        var formName = $(this).attr("name");
        var target   = $(this).attr("target");
        if (formName === "login" || formName === "export" || (typeof target !== "undefined" && target !== "")) {
            return;
        }

        var formURL = $(this).attr("action");
        if (typeof formURL !== "string" || formURL === "" || hasScriptableUrlScheme(formURL)) {
            return;
        }

        // A chosen file can't travel through serializeArray(), so post the whole form as multipart
        // FormData instead. Letting it fall through to a native submit would reload the document,
        // which destroys the web player (it is an ajax-filled div with no state anywhere else).
        // Checked per input via .files so a file in any position counts, not just the first one.
        var hasFile = $(this).find("input[type=file]").filter(function () {
            return this.files && this.files.length > 0;
        }).length > 0;

        var options = {
            url: formURL,
            type: "POST",
            success: function (data, status, jqXHR) {
                loadContentData(data, status, jqXHR);
                // point the address bar at the page the post just rendered
                var posted = ampacheUrl(formURL);
                if (posted) {
                    history.replaceState(null, "", posted.href);
                }
            },
            error: function (jqXHR, status, errorThrown) {
                alert(errorThrown);
            }
        };

        if (hasFile) {
            // must be async: sending a file body on a synchronous XHR is deprecated and browsers
            // refuse it for larger payloads
            options.data        = new FormData(this);
            options.processData = false;
            options.contentType = false;
        } else {
            options.async = false;
            options.data  = $(this).serializeArray();
        }

        $.ajax(options);

        e.preventDefault();
    });

    // Back/forward. history.pushState doesn't fire this, so it only runs for real history moves.
    $(window).on("popstate", function () {
        if (window.location.pathname + window.location.search === loadedPage) {
            return; // fragment-only move, the rendered page is already the right one
        }
        loadContentPage(window.location.href);
    });

    // Legacy hash bookmarks (/index.php#browse.php?action=album) still resolve, and upgrade
    // themselves to the real url. Same single extra fetch these bookmarks have always cost.
    var legacy = window.location.hash.substring(1);
    if (legacy && legacy.indexOf("prettyPhoto") !== 0 && legacy.indexOf(".php") > -1) {
        var legacyTarget = ampacheUrl(jsWebPath + "/" + legacy);
        if (legacyTarget) {
            history.replaceState(null, "", legacyTarget.href);
            loadContentPage(legacyTarget.href);
        }
    }

});

$(document).ajaxSuccess(function() {
    // the page path relative to the web root ("browse.php?action=album"), which is what the hash
    // used to hold, so the regex chain and the 'admin/catalog' style comparisons below still match
    var title = ampachePagePath().replace(/[#$&=_]/g, '');
    title = title.replace(/\?.*/gi, '');
    title = title.replace(/\b(?:action|type|tab|.php|\[\]|[a-z]* id|[0-9]*)\b/gi, '');
    title = title.trim();
    if (jsAmpConfigSongPageTitle) {
        // don't do anything
    } else if (title === 'index') {
        document.title = jsSiteTitle + ' | ' + jsHomeTitle;
    } else if (title === 'browse') {
        document.title = jsSiteTitle + ' | ' + jsBrowseMusicTitle;
    } else if (title === 'albums') {
        document.title = jsSiteTitle + ' | ' + jsAlbumTitle;
    } else if (title === 'artists') {
        document.title = jsSiteTitle + ' | ' + jsArtistTitle;
    } else if (title === 'song') {
        document.title = jsSiteTitle + ' | ' + jsSongTitle;
    } else if (title === 'democratic') {
        document.title = jsSiteTitle + ' | ' + jsDemocraticTitle;
    } else if (title === 'labels') {
        document.title = jsSiteTitle + ' | ' + jsLabelsTitle;
    } else if (title === 'mashup') {
        document.title = jsSiteTitle + ' | ' + jsDashboardTitle;
    } else if (title === 'podcast') {
        document.title = jsSiteTitle + ' | ' + jsPodcastTitle;
    } else if (title === 'podcast_episode') {
        document.title = jsSiteTitle + ' | ' + jsPodcastEpisodeTitle;
    } else if (title === 'radio') {
        document.title = jsSiteTitle + ' | ' + jsRadioTitle;
    } else if (title === 'video') {
        document.title = jsSiteTitle + ' | ' + jsVideoTitle;
    } else if (title === 'localplay') {
        document.title = jsSiteTitle + ' | ' + jsLocalplayTitle;
    } else if (title === 'random') {
        document.title = jsSiteTitle + ' | ' + jsRandomTitle;
    } else if (title === 'playlist') {
        document.title = jsSiteTitle + ' | ' + jsPlaylistTitle;
    } else if (title === 'smartplaylist') {
        document.title = jsSiteTitle + ' | ' + jsSmartPlaylistTitle;
    } else if (title === 'search') {
        document.title = jsSiteTitle + ' | ' + jsSearchTitle;
    } else if (title === 'preferences') {
        document.title = jsSiteTitle + ' | ' + jsPreferencesTitle;
    } else if (title === 'stats') {
        document.title = jsSiteTitle + ' | ' + jsStatisticsTitle;
    } else if (title === 'upload') {
        document.title = jsSiteTitle + ' | ' + jsUploadTitle;
    } else if (title === 'admin/catalog' || title === 'admin/index') {
        document.title = jsSiteTitle + ' | ' + jsAdminCatalogTitle;
    } else if (title === 'admin/users') {
        document.title = jsSiteTitle + ' | ' + jsAdminUserTitle;
    } else if (title === 'admin/mail') {
        document.title = jsSiteTitle + ' | ' + jsAdminMailTitle;
    } else if (title === 'admin/access') {
        document.title = jsSiteTitle + ' | ' + jsAdminManageAccessTitle;
    } else if (title === 'admin/preferences' || title === 'admin/system') {
        document.title = jsSiteTitle + ' | ' + jsAdminPreferencesTitle;
    } else if (title === 'admin/modules') {
        document.title = jsSiteTitle + ' | ' + jsAdminManageModulesTitle;
    } else if (title === 'admin/filter') {
        document.title = jsSiteTitle + ' | ' + jsAdminFilterTitle;
    } else if (title === 'admin/license') {
        document.title = jsSiteTitle + ' | ' + jsAdminLicenseTitle;
    } else {
        document.title = jsSiteTitle;
    }
});

// ajaxPost
// Post the contents of a form.
export function ajaxPost(url, input, source) {
    if ($(source)) {
        $(source).off("click");
    }
    $.ajax(url, { success: processContents, type: "post", data: $("#"+input).serialize() });
} // ajaxPost

// ajaxPut
// Get response from the specified URL.
export function ajaxPut(url, source) {
    if ($(source)) {
        $(source).off("click");
    }
    $.ajax(url, { success: processContents, type: "post", dataType: "xml" });
} // ajaxPut

// ajaxState
// Post the contents of a form without doing any observe() things.
export function ajaxState(url, input) {
    $.ajax({
        url     : url,
        type    : "POST",
        data    : $("#" + input).serialize(true),
        success : processContents
     });
} // ajaxState

// processContents
// Iterate over a response and do any updates we received.
export function processContents(data) {
    $(data).find("content").each(function () {
        // use id attribute selector as workaround for multiple identical IDs (e.g. rating)
        $("[id=" + $(this).attr("div")).html($(this).text());
    });
} // processContents

/* global jsWebPath */

export function loadContentData(data, status, jqXHR)
{
    var $response = $(data);

    if ($response.find("#guts").length === 0) {
        $("body").undelegate("a");
        $("body").undelegate("form");
        $("body").empty().append($response);
    } else {
        var $mainContent = $("#content");
        var $pageWrap    = $("#guts");
        $mainContent.empty().append($response.find("#guts"));
        $mainContent.fadeIn(200, function() {
            $pageWrap.animate({
                height: $mainContent.height() + "px"
            });
        });
        $("a[rel^='prettyPhoto']").prettyPhoto({
            social_tools: false,
            deeplinking: false
        });
        initTabs();
    }
}

export function loadContentPage(url)
{
    var loading = ampacheUrl(url);
    if (loading) {
        loadedPage = loading.pathname + loading.search;
    }
    var $mainContent = $("#content");

    $mainContent
        .find("#guts")
        .fadeOut(200, function() {
            $.get(url, function (data, status, jqXHR) {
                loadContentData(data, status, jqXHR);
            }, "html");
        });
}

var sseSource = null;

// Whitelist of functions the SSE stream may invoke; messages are JSON {"fn": "...", "args": [...]}
// (emitted by SseApiApplication, Ui::update_text and AmpError::add). Lookups are deferred to call time
var sseHandlers = {
    "toggleVisible": function (element) { toggleVisible(element); },
    "displayNotification": function (message, timeout) { displayNotification(message, timeout); },
    "display_sse_error": function (error) { display_sse_error(error); },
    "stop_sse_worker": function () { stop_sse_worker(); }
};

export function sse_worker(url) {
    if(typeof(EventSource) !== "undefined") {
        sseSource = new EventSource(url);
        sseSource.onmessage = function(event) {
            var message;
            try {
                message = JSON.parse(event.data);
            } catch (e) {
                return;
            }
            if (message && Object.prototype.hasOwnProperty.call(sseHandlers, message.fn)) {
                sseHandlers[message.fn].apply(null, message.args || []);
            }
        };
        sseSource.onopen = function() {
            displayNotification("Connected through Server-Sent Events, processing...", 5000);
        };
        sseSource.onerror = function() {
            displayNotification("Server-Sent Events connection error. Re-connection...", 5000);
        };
    } else {
        // Server-Sent Events not supported, call the update in ajax and the output result
        $.get(url + "&html=1", function (data) {
            $("#guts").append(data);
        }, "html");
    }
}

export function stop_sse_worker() {
    if (sseSource !== null) {
        sseSource.close();
        sseSource = null;
    }
}

export function display_sse_error(error) {
    displayNotification("ERROR: " + error, 10000);
}