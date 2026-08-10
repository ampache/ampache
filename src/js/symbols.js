
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

/*
 * Material symbols are drawn as <svg><use href="#ms-name"></use></svg> against a hidden <symbol>
 * definition. A browser re-resolves <use> happily as long as *some* element still carries the id --
 * duplicates are harmless and removing one of them recovers. What kills the icon is the last copy of
 * a definition going away, which leaves <use> dangling and renders nothing at all.
 *
 * Ui::get_material_symbol emits its definition on the symbol's first use in the *request*, tracked in
 * a request-global $_emitted_symbols. An ajax response is not one fragment though: a handler fills a
 * $results map and processContents drops each entry into a different element. So the definition lands
 * in whichever fragment happened to render the icon first (say browse_filters) while the <use> for it
 * in the next fragment (browse_content) has none of its own. It draws correctly, because the id is in
 * the document -- until any later action refreshes the fragment holding the definition, and every
 * other reference to it goes blank. Same story for the page sprite, which Ui::material_symbol_sprite
 * puts at the tail of #guts: navigation replaces #guts wholesale.
 *
 * So no definition is left in markup that something else can replace: every <symbol> is moved, as it
 * arrives, into one store appended to <body> that nothing else touches. A symbol already in the store
 * wins and the arriving duplicate is dropped, so the element a <use> resolves to is never removed.
 */

var STORE_ID = "amp-symbol-store";
var SVG_NS = "http://www.w3.org/2000/svg";

function symbolStore() {
    var store = document.getElementById(STORE_ID);
    if (store) {
        return store;
    }

    if (!document.body) {
        return null;
    }

    store = document.createElementNS(SVG_NS, "svg");
    store.setAttribute("id", STORE_ID);
    store.setAttribute("width", "0");
    store.setAttribute("height", "0");
    store.setAttribute("aria-hidden", "true");
    store.style.position = "absolute";
    document.body.appendChild(store);

    return store;
}

// The wrapper an arriving sprite came in is a zero-sized hidden <svg> holding nothing but definitions.
// Once its symbols have been moved out it draws nothing, so drop it rather than leave it in the page.
function discardEmptyWrapper(wrapper, store) {
    if (
        !wrapper ||
        wrapper === store ||
        wrapper.nodeName.toLowerCase() !== "svg" ||
        wrapper.childElementCount > 0 ||
        !wrapper.parentNode
    ) {
        return;
    }

    wrapper.parentNode.removeChild(wrapper);
}

// What the store already holds. Read off the store rather than through getElementById, which would
// answer with whichever duplicate happens to come first in the document -- the very thing being fixed.
function storedIds(store) {
    var held = Object.create(null);
    for (var i = 0; i < store.children.length; i++) {
        var id = store.children[i].id;
        if (id) {
            held[id] = true;
        }
    }

    return held;
}

/**
 * Move every material-symbol definition below `root` into the page-level store, keeping the copy that
 * got there first. Safe to call as often as you like: with nothing new to move it is a single query.
 *
 * @param {Element|Document} [root] defaults to the whole document
 */
export function hoistMaterialSymbols(root) {
    var scope = root || document;
    if (!scope || typeof scope.querySelectorAll !== "function") {
        return;
    }

    var found = scope.querySelectorAll("symbol[id^='ms-']");
    if (found.length === 0) {
        return;
    }

    var store = symbolStore();
    if (!store) {
        return;
    }

    var held = storedIds(store);

    for (var i = 0; i < found.length; i++) {
        var symbol = found[i];
        var wrapper = symbol.parentNode;
        if (wrapper === store) {
            continue;
        }

        if (held[symbol.id]) {
            if (wrapper) {
                wrapper.removeChild(symbol);
            }
        } else {
            held[symbol.id] = true;
            store.appendChild(symbol);
        }

        discardEmptyWrapper(wrapper, store);
    }
}
