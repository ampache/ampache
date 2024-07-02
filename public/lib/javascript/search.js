/* global types */

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

var rowIter = 1;
var rowCount = 0;
// A search row is the js selection menu created for the search pages
var SearchRow = {
    add(ruleType, operator, input, subtype) {
        if (typeof(ruleType) != "string") {
            ruleType = 0;
        } else {
            jQuery.each(types, function(i) {
                if (types[i].name == ruleType) {
                    ruleType = i;
                    return false;
                }
            });
        }

        if (typeof(operator) != "string") {
            operator = 0;
        }
        else {
            if (ruleType != null) {
                var opts = basetypes[types[ruleType].type];
                jQuery.each(opts, function(i) {
                    if (opts[i].name == operator) {
                        operator = i;
                        return false;
                    }
                });
            }
        }

        var row = document.createElement("tr");
        var cells = new Array();
        for (var i = 0 ; i < 5 ; i++) {
            cells[i] = document.createElement("td");
        }

        cells[0].appendChild(SearchRow.constructOptions(ruleType, rowIter));
        var select = SearchRow.createSubtypeOptions(ruleType, rowIter, subtype);
        if (select) {
            cells[0].appendChild(select);
        }
        cells[1].appendChild(SearchRow.constructOperators(ruleType, rowIter, operator));
        cells[2].appendChild(SearchRow.constructInput(ruleType, rowIter, input));
        cells[3].innerHTML = removeIcon;

        jQuery.each(cells, function(i) {
            row.appendChild(cells[i]);
        });

        $("#searchtable").append(row);
        rowCount++;

        $(cells[3]).on("click", function(){if(rowCount > 1) { $(this).parent().remove(); rowCount--; }});

        rowIter++;
    },
    constructInput(ruleType, ruleNumber, input) {
        if (input == null || typeof input == "undefined") {
            input = "";
        }

        var widget = types[ruleType].widget;

        var inputNode = document.createElement(widget["0"]);
        inputNode.id = "rule_" + ruleNumber + "_input";
        inputNode.name = "rule_" + ruleNumber + "_input";

        switch(widget["0"]) {
            case "input":
                inputNode.setAttribute("type", widget["1"]);
                inputNode.setAttribute("value", SearchRow.decodeEntities(input));
                break;
            case "select":
                // search->_add_type_select()
                var optioncount = 0;
                var keys = Object.keys(widget["1"]);

                // Sort the keys based on their corresponding values in widget["1"]
                keys.sort(function(a, b) {
                    var valA = widget["1"][a];
                    var valB = widget["1"][b];

                    var intA = parseInt(valA, 10);
                    var intB = parseInt(valB, 10);

                    // Check if both values can be parsed as integers before sorting as strings
                    if (!isNaN(intA) && !isNaN(intB)) {
                        return intA - intB;
                    } else {
                        return valA.localeCompare(valB);
                    }
                });

                // Now use jQuery.each
                jQuery.each(keys, function(i, key) {
                    var option = document.createElement("option");
                    var realValue = 0;
                    // only allow ints that parse as ints and match the input
                    if (parseInt(key) == key && parseInt(i) === optioncount) {
                        realValue = key;
                    }
                    else {
                        realValue = i;
                    }
                    if (input == realValue) {
                        option.selected = true;
                    } else {
                        option.selected = false;
                    }
                    // index value or string id of the object
                    option.value = realValue;
                    // text to show in the dropdown
                    option.innerHTML = widget["1"][key];
                    inputNode.appendChild(option);
                    optioncount++;
                });
                if (types[ruleType].type !== "numeric") {
                    SearchRow.sortSelect(inputNode, input);
                }
                break;
            case "subtypes":
                inputNode = document.createElement(widget[1][0]);
                inputNode.id = "rule_" + ruleNumber + "_input";
                inputNode.name = "rule_" + ruleNumber + "_input";
                inputNode.setAttribute("type", widget[1][1]);
                inputNode.setAttribute("value", SearchRow.decodeEntities(input));
                break;
        }

        return inputNode;
    },
    constructOptions(ruleType, ruleNumber) {
        var optionsNode = document.createElement("select");
        optionsNode.id = "rule_" + ruleNumber;
        optionsNode.name = "rule_" + ruleNumber;
        var groups = [];
        jQuery.each(types, function(i) {
            if (groups.indexOf(types[i].title) === -1) {
                groups.push(types[i].title);
            }
        });
        for (g = 0; g < groups.length; g++) {
            var optGroupNode = document.createElement("optgroup");
            optGroupNode.label = groups[g];

            jQuery.each(types, function (i) {
                if (groups[g] === types[i].title) {
                    var option = document.createElement("option");
                    option.innerHTML = types[i].label;
                    option.value = types[i].name;
                    if (i == ruleType) {
                        option.selected = true;
                    }
                    if (groups[g] === "") {
                        optionsNode.appendChild(option);
                    } else {
                        optGroupNode.appendChild(option);
                    }
                }
            });
            if (groups[g] !== "") {
                optionsNode.appendChild(optGroupNode);
            }
        }

        $(optionsNode).change(SearchRow.update);

        return optionsNode;
    },
    constructOperators(ruleType, ruleNumber, operator) {
        var operatorNode = document.createElement("select");
        operatorNode.id = "rule_" + ruleNumber + "_operator";
        operatorNode.name = "rule_" + ruleNumber + "_operator";

        var basetype = types[ruleType].type;
        operatorNode.className = "operator" + basetype;

        var opts = basetypes[basetype];
        jQuery.each(opts, function(i) {
            var option = document.createElement("option");
            option.innerHTML = opts[i].description;
            option.value = i;
            if (i == operator) {
                option.selected = true;
            }
            operatorNode.appendChild(option);
        });

        return operatorNode;
    },
    update() {
        var r_findID = /rule_(\d+)/;
        var targetID = r_findID.exec(this.id)[1];

        var operator = $("#rule_" + targetID + "_operator");
        if (operator.className != "operator" + types[this.selectedIndex].type) {
            var operator_cell = operator.parent();
            operator.remove();
            operator_cell.append(SearchRow.constructOperators(this.selectedIndex, targetID));
        }

        var type = $(this).val();

        jQuery.each(types, function (index, value) {
            if (value.name == type) {
                type = value;
                return false;
            }
        });

        if (type.widget[0] == "subtypes") {
            var $select = SearchRow.createSelect({
                name: "rule_" + targetID + "_subtype"
            }, type.subtypes, undefined, true);
            $(this).after($select);
        }
        else {
            $(this).closest("tr").find("select[name$=\"subtype\"]").remove();
        }

        var input = $("#rule_" + targetID + "_input");
        if (input.type == "text") {
            var oldinput = input.value;
        }

        var input_cell = input.parent();
        input.remove();
        input_cell.append(SearchRow.constructInput(this.selectedIndex, targetID, oldinput));
    },
    createSelect(attributes, options, selected, sort=false) {
        // used for metadata selections
        var $select = $("<select>");
        $.each(attributes, function (key, value) {
            $select.attr(key, value);
        });

        let optionValues = Object.keys(options);
        if (sort) {
            optionValues = optionValues.sort(function(a,b){
                return options[a].toLowerCase() > options[b].toLowerCase() ? 1 : -1;
            });
        }
        optionValues.forEach(function (value, index) {
            $("<option>").attr("value", value).text(options[value]).appendTo($select);
        });
        $select.val(selected);
        return $select;
    },
    createSubtypeOptions(ruleType, ruleNumber, subtype) {
        var type = types[ruleType];

        if (type["widget"][0] == "subtypes") {
            var $input = SearchRow.createSelect({
                name: "rule_" + ruleNumber + "_subtype"
            }, type.subtypes, subtype, true);
            return $input[0];
        }
    },
    sortSelect(selectElement, selectedIndex) {
        // Sort selection arrays
        var sortArray = [];
        for (var i=0; i < selectElement.options.length; i++) {
            sortArray[i] = [];
            sortArray[i][0] = selectElement.options[i].text;
            sortArray[i][1] = selectElement.options[i].value;
            sortArray[i][2] = selectElement.options[i].selected;
        }
        sortArray.sort();
        while (selectElement.options.length > 0) {
            selectElement.options[0] = null;
        }
        for (var i=0; i < sortArray.length; i++) {
            selectElement.options[i] = new Option(sortArray[i][0], sortArray[i][1], false, sortArray[i][2]);
        }
    },
    decodeEntities(encodedString) {
        var tempElement = document.createElement('div');
        tempElement.innerHTML = encodedString;
        return tempElement.textContent || tempElement.innerText;
    }
};
