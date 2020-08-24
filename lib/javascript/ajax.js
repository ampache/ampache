
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

// Some cutesy flashing thing while we run
$(document).ajaxSend(function () {
    $("#ajax-loading").show();
});
$(document).ajaxComplete(function () {
    $("#ajax-loading").hide();
});

// ajaxPost
// Post the contents of a form.
function ajaxPost(url, input, source) {
    if ($(source)) {
        $(source).off("click");
    }
    $.ajax(url, { success: processContents, type: "post", data: $("#"+input).serialize() });
} // ajaxPost

// ajaxPut
// Get response from the specified URL.
function ajaxPut(url, source) {
    if ($(source)) {
        $(source).off("click");
    }
    $.ajax(url, { success: processContents, type: "post", dataType: "xml" });
} // ajaxPut

// ajaxState
// Post the contents of a form without doing any observe() things.
function ajaxState(url, input) {
    $.ajax({
        url     : url,
        type    : "POST",
        data    : $("#" + input).serialize(true),
        success : processContents
     });
} // ajaxState

// processContents
// Iterate over a response and do any updates we received.
function processContents(data) {
    $(data).find("content").each(function () {
        $("#" + $(this).attr("div")).html($(this).text());
    });
} // processContents

