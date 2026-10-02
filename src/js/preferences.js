/* global ampacheConfirm */

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

/***************/
/* Preferences */
/***************/

import {onScopeAdded} from './base.js';

// The preferences table, enhanced. Undo and "back to default" read data-default and data-initial, so they
// cost no request, and the page still saves without this file: the submit button ships enabled.

// Saving swaps #guts out from under us; the flag survives because this module is not reloaded.
var preferencesPendingScroll = false;

function preferencesScope(element) {
    return $(element).closest("[data-preferences-scope]");
}

function preferencesControls($scope) {
    return $scope.find(".pref-control[data-pref]");
}

// A multi-select posts several values; everything else posts one. Both compare as one comma-joined string.
function preferencesValue($control) {
    var value = $control.val();

    return (value === null) ? "" : [].concat(value).join(",");
}

function preferencesRow($control) {
    return $control.closest(".pref-row");
}

// The rows whose control no longer holds what is stored, with both values as they read on screen.
function preferencesChanged($scope) {
    var changed = [];

    preferencesControls($scope).each(function () {
        var $control = $(this);
        var current = preferencesValue($control);
        var initial = $control.attr("data-initial");
        var $row = preferencesRow($control);
        var applyAll = $row.find("[data-pref-apply]").prop("checked") === true;

        // ticking the box alone still writes this value into every account, so it counts as a change
        if (current !== initial || applyAll) {
            changed.push({
                label: $row.find(".pref-label").text().trim(),
                from: preferencesReadable($control, initial),
                to: preferencesReadable($control, current),
                applyAll: applyAll
            });
        }
    });

    // the level is written whether or not the value moved, so the recap has to see it too
    $scope.find("select[data-pref-level]").each(function () {
        var $level = $(this);
        var initial = $level.attr("data-initial");

        if (String($level.val()) === initial) {
            return;
        }

        var $row = $level.closest(".pref-row");
        var $was = $level.find("option").filter(function () {
            return this.value === initial;
        });

        changed.push({
            label: $row.find(".pref-label").text().trim()
                + " \u2014 " + ($scope.find("[data-pref-summary]").attr("data-pref-level-label") || ""),
            from: $was.text(),
            to: $level.find("option:selected").text(),
            applyAll: false
        });
    });

    return changed;
}

function preferencesChangeList($scope, changed) {
    var everyone = $scope.find("[data-pref-summary]").attr("data-pref-everyone") || "";

    return $("<ul>").addClass("pref-summary-list").append(changed.map(function (entry) {
        var $line = $("<li>").append($("<span>").addClass("pref-summary-name").text(entry.label));

        if (entry.from !== entry.to) {
            $line.append($("<span>").addClass("pref-summary-from").text(entry.from))
                .append($("<span>").addClass("pref-summary-arrow").text("\u2192"))
                .append($("<span>").addClass("pref-summary-to").text(entry.to));
        } else {
            $line.append($("<span>").addClass("pref-summary-to").text(entry.to));
        }

        // saying "everyone" only on the lines it applies to is the whole point of the per-row box
        if (entry.applyAll) {
            $line.append($("<span>").addClass("pref-summary-everyone").text(everyone));
        }

        return $line;
    }));
}

function preferencesRefresh($scope) {
    // the label promises every change, so a row that turns dirty later is ticked too
    var applyAll = $scope.find("[data-pref-apply-all-toggle]").prop("checked") === true;

    preferencesControls($scope).each(function () {
        var $control = $(this);
        var current = preferencesValue($control);
        var $row = preferencesRow($control);
        var isDirty = current !== $control.attr("data-initial");
        var isDiff = current !== $control.attr("data-default");

        // the server settled "differs from default" at render, including for rows with no control at all;
        // only a live edit can move it, so an untouched row keeps the answer it was given
        var differs = (isDirty) ? isDiff : $row.attr("data-pref-differs") === "1";

        $row.toggleClass("pref-row-dirty", isDirty);
        $row.attr("data-pref-differs", differs ? "1" : "0");
        $row.find(".pref-badge-dirty").prop("hidden", !isDirty);
        $row.find(".pref-badge-diff").prop("hidden", isDirty || !differs);
        $row.find("[data-pref-action='revert']").prop("disabled", !isDirty);
        $row.find("[data-pref-action='default']").prop("disabled", !isDiff);
        $row.find("[data-pref-action='server']").prop("disabled", current === $control.attr("data-server"));
        $row.find("[data-pref-zero]").prop("hidden", current !== "0");
        if (applyAll && isDirty) {
            $row.find("[data-pref-apply]").prop("checked", true);
        }
    });

    // one source for what changed, so the folded recap and the confirmation can never disagree
    var changed = preferencesChanged($scope);

    $scope.find("[data-pref-dirty-count]").text(changed.length);
    $scope.find("[data-pref-submit]").prop("disabled", changed.length === 0);
    preferencesSummary($scope, changed);
    preferencesApplyFilter($scope);
}

// What a stored value looks like on screen: a select says "Active", not "1".
function preferencesReadable($control, value) {
    var $summary = preferencesScope($control).find("[data-pref-summary]");
    var empty = $summary.attr("data-pref-empty-label") || "";

    // a secret is write-only: what was typed must not surface in the recap or the confirmation
    if ($control.is("[data-pref-secret]")) {
        return (value === "") ? empty : ($summary.attr("data-pref-secret-label") || "");
    }

    if (!$control.is("select")) {
        return (value === "") ? empty : value;
    }

    var labels = String(value).split(",").map(function (part) {
        var $option = $control.find("option").filter(function () {
            return this.value === part;
        });

        return ($option.length !== 0) ? $option.first().text() : part;
    });

    return (value === "") ? empty : labels.join(", ");
}

// A count that unfolds into "what becomes what", rather than a run-on list of names.
function preferencesSummary($scope, changed) {
    var $summary = $scope.find("[data-pref-summary]");

    $summary.prop("hidden", changed.length === 0);
    if (changed.length === 0) {
        return;
    }

    var template = $summary.attr("data-pref-summary-label");

    $summary.find("[data-pref-summary-title]").text(String(template || "%d").replace("%d", changed.length));
    $summary.find("[data-pref-summary-list]").replaceWith(
        preferencesChangeList($scope, changed).attr("data-pref-summary-list", "")
    );
}

// Filtering hides rows rather than removing them, so the fields keep posting whatever they hold.
function preferencesApplyFilter($scope) {
    var needle = ($scope.find("[data-pref-filter]").val() || "").toString().trim().toLowerCase();
    var mode = $scope.find("[data-pref-scope-filter][aria-pressed='true']").attr("data-pref-scope-filter") || "all";
    var shown = 0;

    $scope.find(".pref-row").each(function () {
        var $row = $(this);
        var matches = needle === "" || ($row.attr("data-search") || "").indexOf(needle) !== -1;

        if (matches && mode === "differing") {
            matches = $row.attr("data-pref-differs") === "1";
        }

        if (matches && mode === "changed") {
            matches = $row.hasClass("pref-row-dirty");
        }

        $row.prop("hidden", !matches);
        if (matches) {
            $row.toggleClass("pref-row-odd", shown % 2 === 0);
            shown += 1;
        }
    });

    // a subcategory heading with nothing under it reads as an empty section
    $scope.find(".pref-subcategory").each(function () {
        var $heading = $(this);

        $heading.prop("hidden", $heading.nextUntil(".pref-subcategory", ".pref-row:not([hidden])").length === 0);
    });

    $scope.find("[data-pref-empty]").prop("hidden", shown !== 0);
}

function preferencesSet($control, value) {
    if ($control.attr("multiple")) {
        $control.val(value === "" ? [] : value.split(","));
    } else {
        $control.val(value);
    }

    $control.trigger("change");
}

$(document).on("input change", "[data-preferences-scope] .pref-control", function () {
    preferencesRefresh(preferencesScope(this));
});

$(document).on("input", "[data-pref-filter]", function () {
    var $scope = preferencesScope(this);

    $scope.find("[data-pref-clear]").prop("hidden", $(this).val() === "");
    preferencesApplyFilter($scope);
});

$(document).on("change", "[data-pref-apply-all-toggle]", function () {
    preferencesRefresh(preferencesScope(this));
});

$(document).on("change", "[data-pref-apply]", function () {
    preferencesRefresh(preferencesScope(this));
});

$(document).on("change", "select[data-pref-level]", function () {
    preferencesRefresh(preferencesScope(this));
});

$(document).on("click", "[data-pref-clear]", function () {
    var $scope = preferencesScope(this);

    $scope.find("[data-pref-filter]").val("").trigger("input").trigger("focus");
});

$(document).on("click", "[data-pref-scope-filter]", function () {
    var $scope = preferencesScope(this);

    $scope.find("[data-pref-scope-filter]").attr("aria-pressed", "false");
    $(this).attr("aria-pressed", "true");
    preferencesApplyFilter($scope);
});

$(document).on("click", "[data-pref-action]", function () {
    var $button = $(this);
    var $scope = preferencesScope(this);
    var $control = $scope.find("[data-pref='" + $button.attr("data-pref-target") + "']");
    var source = {"default": "data-default", server: "data-server", revert: "data-initial"}[$button.attr("data-pref-action")];

    // an unrecognised action would read attr(undefined) and blank the preference
    if (source === undefined || $control.length === 0) {
        return;
    }

    preferencesSet($control, $control.attr(source) || "");
});

// `ajax.js` delegates submit from `body`, so only a direct binding on the form runs early enough to stop it
function preferencesBindForm($scope) {
    var $form = $scope.find("form").first();

    if ($form.length === 0 || $form.data("prefBound")) {
        return;
    }

    $form.data("prefBound", true).on("submit", function (event) {
        var changed = preferencesChanged($scope);

        if ($form.data("prefConfirmed") || changed.length === 0) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        var $summary = $scope.find("[data-pref-summary]");
        var $body = $("<div>").addClass("pref-confirm").append(preferencesChangeList($scope, changed));
        var spread = changed.filter(function (entry) {
            return entry.applyAll;
        }).length;

        // overwriting other people's accounts cannot be undone, so it is counted out before it happens
        if (spread !== 0) {
            $body.prepend($("<p>").addClass("pref-confirm-warning")
                .text(String($summary.attr("data-pref-warn-everyone") || "%d").replace("%d", spread)));
        }

        ampacheConfirm($body, {title: $summary.attr("data-pref-confirm-title"), width: 560}).then(function (confirmed) {
            if (!confirmed) {
                return;
            }

            if (window.location.hash.length > 1 && window.history.replaceState) {
                window.history.replaceState(null, "", window.location.pathname + window.location.search);
            }

            preferencesPendingScroll = true;
            $form.data("prefConfirmed", true).trigger("submit");
        });
    });
}

onScopeAdded("[data-preferences-scope]", function ($scope) {
    preferencesBindForm($scope);
    preferencesRefresh($scope);

    // the saved notification sits at the top of the page, so that is where the reader has to end up
    if (preferencesPendingScroll) {
        preferencesPendingScroll = false;
        setTimeout(function () {
            $("html, body").scrollTop(0);
        }, 0);
    }
});
