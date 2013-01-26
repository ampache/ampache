// vim:set softtabstop=4 shiftwidth=4 expandtab: 
//
// Copyright 2010 - 2013 Ampache.org
// All rights reserved.
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License v2
// as published by the Free Software Foundation.
// 
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

var rowIter = 1;
var rowCount = 0;

var SearchRow = {
    add: function(ruleType, operator, input) {
        if (typeof(ruleType) != 'string') {
            ruleType = 0;
        }
        else {
            types.each(function(i) {
                if (i.value.name == ruleType) {
                    ruleType = i.key;
                    throw $break;
                }
            });
        }

        if (typeof(operator) != 'string') {
            operator = 0;
        }
        else {
            $H(basetypes.get(types.get(ruleType).type)).each(function(i) {
                if (i.value.name == operator) {
                    operator = i.key;
                    throw $break;
                }
            });
        }

        var row = document.createElement('tr');
        var cells = new Array();
        for (var i = 0 ; i < 5 ; i++) {
            cells[i] = document.createElement('td');
        }
        
        cells[0].appendChild(SearchRow.constructOptions(ruleType, rowIter));
        cells[1].appendChild(SearchRow.constructOperators(ruleType, rowIter, operator));
        cells[2].appendChild(SearchRow.constructInput(ruleType, rowIter, input));
        cells[3].innerHTML = removeIcon;

        cells.each(function(i) {
            row.appendChild(i);
        });

        $('searchtable').appendChild(row);
        rowCount++;

        Event.observe(cells[3], 'click', function(e){if(rowCount > 1) { Element.remove(this.parentNode); rowCount--; }});

        rowIter++;
    }, 
    constructInput: function(ruleType, ruleNumber, input) {
        if (input === null || input === undefined) {
            input = '';
        }

        widget = $H(types.get(ruleType).widget);

        var inputNode  = document.createElement(widget.get('0'));
        inputNode.id   = 'rule_' + ruleNumber + '_input';
        inputNode.name = 'rule_' + ruleNumber + '_input';

        switch(widget.get('0')) {
            case 'input':
                inputNode.setAttribute('type', widget.get('1'));
                inputNode.setAttribute('value', input);
            break;
            case 'select':
                $H(widget.get('1')).each(function(i) {
                    var option = document.createElement('option');
                    if ( isNaN(parseInt(i.value)) ) {
                        realvalue = i.key;
                    }
                    else {
                        realvalue = parseInt(i.value);
                    }
                    if ( input == realvalue ) {
                        option.selected = true;
                    }
                    option.value = realvalue;
                    option.innerHTML = i.value;
                    inputNode.appendChild(option);
                });
            break;
        }

        return inputNode;
    },
    constructOptions: function(ruleType, ruleNumber) {
        var optionsNode  = document.createElement('select');
        optionsNode.id   = 'rule_' + ruleNumber;
        optionsNode.name = 'rule_' + ruleNumber;

        types.each(function(i) {
            var option = document.createElement('option');
            option.innerHTML = i.value.label;
            option.value = i.value.name;
            if ( i.key == ruleType ) {
                option.selected = true;
            }
            optionsNode.appendChild(option);
        });

        Event.observe(optionsNode, 'change', SearchRow.update);

        return optionsNode;
    },
    constructOperators: function(ruleType, ruleNumber, operator) {
        var operatorNode    = document.createElement('select');
        operatorNode.id        = 'rule_' + ruleNumber + '_operator';
        operatorNode.name    = 'rule_' + ruleNumber + '_operator';

        basetype = types.get(ruleType).type;
        operatorNode.className    = 'operator' + basetype;

        $H(basetypes.get(basetype)).each(function(i) {
            var option = document.createElement('option');
            option.innerHTML = i.value.description;
            option.value = i.key;
            if (i.key == operator) {
                option.selected = true;
            }
            operatorNode.appendChild(option);
        });

        return operatorNode;
    },
    update: function() {
        var r_findID = /rule_(\d+)/;
        var targetID = r_findID.exec(this.id)[1];

        var operator = $('rule_' + targetID + '_operator');
        if (operator.className != 'operator' + types.get(this.selectedIndex).type) {
            var operator_cell = operator.parentNode;
            Element.remove(operator);
            operator_cell.appendChild(SearchRow.constructOperators(this.selectedIndex, targetID));
        }

        var input = $('rule_' + targetID + '_input');

        if (input.type == 'text') {
            var oldinput = input.value;
        }

        var input_cell = input.parentNode;
        Element.remove(input);
        input_cell.appendChild(SearchRow.constructInput(this.selectedIndex, targetID, oldinput));
    }
};
