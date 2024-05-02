
/* vim:set softtabstop=4 shiftwidth=4 expandtab:
*
* LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

// Some cutesy flashing thing while we run
$(document).ajaxSend(function () {
    $("#ajax-loading").show();
});
$(document).ajaxComplete(function () {
    $("#ajax-loading").hide();
});

$(function() {

    var newHash      = "";

    $("body").delegate("a", "click", function() {
        var link = $(this).attr("href");
        if (typeof link !== "undefined" && link !== "" && link.indexOf("javascript:") !== 0 && link !== "#" && typeof link !== "undefined" && typeof $(this).attr("onclick") === "undefined" && !$(this).hasClass("nohtml") && $(this).attr("target") !== "_blank") {
            if ($(this).attr("rel") !== "prettyPhoto") {
                // Ajax load Ampache pages only
                if (link.indexOf(jsWebPath) > -1) {
                    window.location.hash = link.substring(jsWebPath.length + 1);
                    return false;
                }
            } else {
                window.location.hash = $(this).attr("rel");
                return false;
            }
        }
    });

    $("body").delegate("form", "submit", function(e) {
        // We do not support ajax post with files or login form, neither specific target
        var $file = $(this).find("input[type=file]");
        if ($(this).attr("name") !== "login" && $(this).attr("name") !== "export" && (!$file || !$file.val() || $file.val() === "") && (typeof $(this).attr("target") === "undefined" || $(this).attr("target") === "")) {
            var postData = $(this).serializeArray();
            var formURL = $(this).attr("action");

            if (formURL.indexOf("javascript:") !== 0) {
                $.ajax(
                    {
                        url: formURL,
                        type: "POST",
                        async: false,
                        data: postData,
                        success:function(data, status, jqXHR)
                        {
                            loadContentData(data, status, jqXHR);
                            window.location.hash = "";
                        },
                        error(jqXHR, status, errorThrown)
                        {
                            alert(errorThrown);
                        }
                    });

                e.preventDefault();
            }
        }
    });

    $(window).bind("hashchange", function(){
        newHash = window.location.hash.substring(1);
        if (newHash && newHash.indexOf("prettyPhoto") !== 0 && newHash.indexOf(".php") > -1) {
            loadContentPage(jsWebPath + "/" + newHash);
            return false;
        }
    });

    $(window).trigger("hashchange");

});

$(document).ajaxSuccess(function() {
    var title = window.location.hash.replace(/[#$&=_]/g, '');
    title = title.replace(/\?.*/gi, '');
    title = title.replace(/\b(?:action|type|tab|.php|\[\]|[a-z]* id|[0-9]*)\b/gi, '');
    title = title.trim();
    if (title === 'index') {
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
    } else if (title === 'video' || title === 'tvshow_seasons' || title === 'tvshows') {
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
        $("#" + $(this).attr("div")).html($(this).text());
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
export function sse_worker(url) {
    if(typeof(EventSource) !== "undefined") {
        sseSource = new EventSource(url);
        sseSource.onmessage = function(event) {
            eval(event.data);
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