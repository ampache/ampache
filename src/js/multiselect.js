/* global ajaxPut, showPlaylistDialog, ampacheConfirm */

/* vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

/****************/
/* Multi select */
/****************/

// Row selection for browse tables. A scope element wraps one action bar and one table; the bar's links carry
// a URL template whose {ids}, {track_ids} and {type} placeholders are filled in from the current selection.
// Every handler is delegated from document, so a browse replaced by an AJAX refresh keeps working untouched.

// Remembers the last row clicked per scope so that a following shift-click can select the range between them.
var multiSelectAnchor = new WeakMap();

function multiSelectScope(element) {
    return $(element).closest("[data-multiselect-scope]");
}

function multiSelectItems($scope) {
    return $scope.find("input.multiselect-item");
}

// The selection in row order. Each entry keeps the object id, its type and the playlist track id, because
// removing from a playlist addresses the track row while playing addresses the media sitting behind it.
function multiSelectSelection($scope) {
    return multiSelectItems($scope).filter(":checked").map(function () {
        return {
            id: $(this).attr("data-id"),
            type: $(this).attr("data-type"),
            trackId: $(this).attr("data-track-id")
        };
    }).get();
}

// Groups a selection by object type. Playlists may hold songs, videos and podcast episodes together while the
// playback and basket actions each take a single type, so one request per type is the only correct way.
function multiSelectGroupByType(selection) {
    var groups = [];
    var index = {};

    selection.forEach(function (item) {
        var type = item.type || "";
        if (index[type] === undefined) {
            index[type] = groups.length;
            groups.push({type: type, ids: []});
        }
        groups[index[type]].ids.push(item.id);
    });

    return groups;
}

// Reflects the current count into the bar: the summary updates and the actions disable at zero so the bar can
// never fire a request with an empty id list. The add-to-playlist dialog carries a single type, so a
// selection spanning several types disables it rather than silently adding only part of what was picked.
function multiSelectRefresh($scope) {
    var $items = multiSelectItems($scope);
    var count = $items.filter(":checked").length;
    var mixed = multiSelectGroupByType(multiSelectSelection($scope)).length > 1;

    $scope.find("[data-multiselect-count]").text(count);
    $scope.find("[data-multiselect-bar]").toggleClass("multiselect-empty", count === 0);
    $scope.find("[data-multiselect-action]").attr("aria-disabled", count === 0 ? "true" : "false");
    $scope.find("[data-multiselect-action='playlist']").attr("aria-disabled", (count === 0 || mixed) ? "true" : "false");

    var $all = $scope.find("input.multiselect-all");
    $all.prop("checked", count > 0 && count === $items.length);
    $all.prop("indeterminate", count > 0 && count < $items.length);
}

function multiSelectFill(template, replacements) {
    return Object.keys(replacements).reduce(function (url, key) {
        return url.split("{" + key + "}").join(replacements[key]);
    }, template);
}

// A track_ids template addresses playlist rows directly, so the type is irrelevant and one request covers the
// whole selection. Anything else goes per type, with later groups forced to append so play does not reset.
function multiSelectRequests(template, selection) {
    if (template.indexOf("{track_ids}") !== -1) {
        var trackIds = selection.map(function (item) {
            return item.trackId;
        });

        return [multiSelectFill(template, {track_ids: trackIds.join(",")})];
    }

    return multiSelectGroupByType(selection).map(function (group, position) {
        var url = multiSelectFill(template, {type: group.type, ids: group.ids.join(",")});
        var appends = url.indexOf("playnext=true") !== -1 || url.indexOf("append=true") !== -1;

        return (position > 0 && !appends) ? url + "&append=true" : url;
    });
}

// Clicking a row checkbox with shift held selects every row between the anchor and this one, matching the
// range behaviour of a file manager. The anchor then moves here so that ranges can be chained.
$(document).on("click", "input.multiselect-item", function (event) {
    var $scope = multiSelectScope(this);
    if ($scope.length === 0) {
        return;
    }

    var $items = multiSelectItems($scope);
    var anchor = multiSelectAnchor.get($scope[0]);
    var position = $items.index(this);

    if (event.shiftKey && anchor !== undefined && anchor !== position) {
        var checked = $(this).prop("checked");
        $items.slice(Math.min(anchor, position), Math.max(anchor, position) + 1).prop("checked", checked);
    }

    multiSelectAnchor.set($scope[0], position);
    multiSelectRefresh($scope);
});

$(document).on("click", "input.multiselect-all", function () {
    var $scope = multiSelectScope(this);
    if ($scope.length === 0) {
        return;
    }

    multiSelectItems($scope).prop("checked", $(this).prop("checked"));
    multiSelectAnchor.delete($scope[0]);
    multiSelectRefresh($scope);
});

// Bar actions. "ajax" replays the selection through the same ajaxPut every other browse button uses, and
// "playlist" hands the id list to the existing add-to-playlist dialog, which already accepts a list.
$(document).on("click", "a[data-multiselect-action]", function (event) {
    event.preventDefault();

    if ($(this).attr("aria-disabled") === "true") {
        return;
    }

    var $scope = multiSelectScope(this);
    var selection = multiSelectSelection($scope);
    if (selection.length === 0) {
        return;
    }

    var action = $(this).attr("data-multiselect-action");
    var template = $(this).attr("data-multiselect-url") || "";
    var confirmText = $(this).attr("data-multiselect-confirm");

    if (action === "playlist") {
        showPlaylistDialog(event, selection[0].type, multiSelectGroupByType(selection)[0].ids.join(","));

        return;
    }

    function run() {
        multiSelectRequests(template, selection).forEach(function (url) {
            ajaxPut(url, "");
        });
    }

    // Non-blocking confirm, matching the delegated data-ajax-confirm handling of the ordinary row buttons.
    if (confirmText) {
        ampacheConfirm(confirmText.replace("{count}", selection.length)).then(function (ok) {
            if (ok) {
                run();
            }
        });

        return;
    }

    run();
});

$(function () {
    $("[data-multiselect-scope]").each(function () {
        multiSelectRefresh($(this));
    });
});

// Legacy helper for the checkbox lists that predate the scoped bar above, where the only interaction is one
// link toggling every box named "<name>[]" at once (the admin Disabled Songs table).
export function check_select(name) {
    var $boxes = $("input[name='" + name + "[]']");

    $boxes.prop("checked", $boxes.filter(":checked").length !== $boxes.length);
}
