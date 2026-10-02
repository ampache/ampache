/* global jsRowlistMore */

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

/*************************/
/* Row lists (narrow UI) */
/*************************/

import {onScopeAdded} from './base.js';

// A browse table marked `rowlist` becomes two-line rows on a narrow screen; the columns that fit neither line
// are folded behind the chevron added here, because no renderer knows the width the page will be read at.

function rowlistToggleCell(row) {
    var cell = document.createElement("td");
    var button = document.createElement("button");

    cell.className = "cel_expand";
    button.className = "rowlist-toggle";
    button.type = "button";
    button.setAttribute("aria-expanded", row.classList.contains("rowlist-open") ? "true" : "false");
    button.setAttribute("aria-label", jsRowlistMore);
    cell.appendChild(button);

    return cell;
}

// An icon already carries its wording in its own <title>, so a folded action can name itself for free. An
// anchor that spells its own action out keeps it: the <title> inside its svg is not text the reader sees.
function rowlistLabelActions(row) {
    $(row).find("td.cel_action a, td.cel_add .cel_item_add a, td.cel_add_list .cel_item_add a").each(function () {
        var title = this.querySelector("svg > title");
        var text = (title !== null) ? title.textContent : (this.getAttribute("title") || "");
        var shown = Array.prototype.filter.call(this.childNodes, function (node) {
            return node.nodeName.toLowerCase() !== "svg";
        }).map(function (node) {
            return node.textContent;
        }).join("").trim();

        if (text === "" || shown !== "") {
            return;
        }

        var label = document.createElement("span");

        label.className = "rowlist-action-text";
        label.textContent = text;
        this.appendChild(label);
    });
}

// A row spanning the whole table is a placeholder ("found nothing to show") and has nothing to fold away.
function rowlistEquip(row) {
    if (row.querySelector(":scope > td") === null || row.querySelector(":scope > td[colspan]") !== null) {
        return;
    }

    if (row.querySelector("td.cel_expand") === null) {
        row.appendChild(rowlistToggleCell(row));
    }

    if (row.classList.contains("rowlist-open")) {
        rowlistLabelActions(row);
    }
}

// Reached with a table on first paint, on a browse refresh and on each page the infinite scroll appends, and
// with a cell when an edit replaces the contents of the row it just saved.
function rowlistApply($node) {
    var node = $node[0];

    if (node.tagName === "TD") {
        rowlistEquip(node.parentNode);

        return;
    }

    if (node.tagName === "TR") {
        rowlistEquip(node);

        return;
    }

    $node.find("> thead > tr, > tfoot > tr").each(function () {
        if (this.querySelector("th.cel_expand") === null) {
            var header = document.createElement("th");

            header.className = "cel_expand";
            this.appendChild(header);
        }
    });

    $node.find("> tbody > tr").each(function () {
        rowlistEquip(this);
    });
}

// One panel at a time: two open rows push the list under the thumb and the reader loses the place they were at.
function rowlistToggle(button) {
    var row = button.closest("tr");
    var open = row.classList.contains("rowlist-open");

    $(row.parentNode).children("tr.rowlist-open").each(function () {
        this.classList.remove("rowlist-open");
        $(this).children("td.cel_expand").find("button.rowlist-toggle").attr("aria-expanded", "false");
    });

    if (!open) {
        rowlistLabelActions(row);
        row.classList.add("rowlist-open");
        button.setAttribute("aria-expanded", "true");
    }
}

$(document).on("click", "button.rowlist-toggle", function () {
    rowlistToggle(this);
});

onScopeAdded("table.rowlist, table.rowlist > tbody > tr, table.rowlist > tbody > tr > td", rowlistApply);
