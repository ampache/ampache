// vim:set softtabstop=4 shiftwidth=4 expandtab:
//
// Copyright 2001 - 2015 Ampache.org
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
//
$(document).ready(function () {
    initTabs();
	$.ajaxSetup({
		// Enable caching of AJAX responses, including script and jsonp
		cache: true
	});
	$('#notification').click(function() {
		clearNotification();
	});
});

function initTabs()
{
    $('.default_hidden').hide();

    $("#tabs li").click(function() {
        $("#tabs li").removeClass('tab_active');
        $(this).addClass("tab_active");
        $(".tab_content").hide();
        var selected_tab = $(this).find("a").attr("href");
        $(selected_tab).fadeIn();

        return false;
    });
}

$(function() {
    var rightmenu = $("#rightbar");
    var pos = rightmenu.offset();
    if (rightmenu.hasClass('rightbar-float')) {
        $(window).scroll(function() {
            var rightsubmenu = $("#rightbar .submenu");
            if ($(this).scrollTop() > (pos.top)) {
                rightmenu.addClass('fixedrightbar');
                rightsubmenu.addClass('fixedrightbarsubmenu');
            }
            else if ($(this).scrollTop() <= pos.top && rightmenu.hasClass('fixedrightbar')) {
                rightmenu.removeClass('fixedrightbar');
                rightsubmenu.removeClass('fixedrightbarsubmenu');
            }
            else {
                rightmenu.offset({ left: pos.left, top: pos.top });
            }
        })
    }
});

// flipField
// Toggles the disabled property on the specifed field
function flipField(field) {
    if ($(field).disabled == false) {
        $(field).disabled = true;
    }
    else {
        $(field).disabled = false;
    }
}

// updateText
// Changes the specified elements innards. Used for the catalog mojo fluff.
function updateText(field, value) {
    $('#'+field).html(value);
}

// toggleVisible
// Toggles display type between block and none. Used for ajax loading div.
function toggleVisible(element) {
    var target = $('#' + element);
    if (target.is(':visible')) {
        target.hide();
    } else {
        target.show();
    }
}

var notificationTimeout = null;
function displayNotification(message, timeout) {
    if (notificationTimeout != null || !message) {
        clearNotification();
    }
	
	if (message) {
		if ($('#webplayer').css('display') !== 'block') {
			$('#notification').css('bottom', '20px');
		} else {
			$('#notification').css('bottom', '120px');
		}
		$('#notification-content').html(message);
		$('#notification').removeClass('notification-out');
		notificationTimeout = setTimeout(function() {
			clearNotification();
		}, timeout);
	}
}

function clearNotification() {
	clearTimeout(notificationTimeout);
	notificationTimeout = null;
	$('#notification').addClass("notification-out");
}

// delayRun
// This function delays the run of another function by X milliseconds
function delayRun(element, time, method, page, source) {
    var function_string = method + '(\'' + page + '\',\'' + source + '\')';
    var action = function () { eval(function_string); };

    if (element.zid) {
        clearTimeout(element.zid);
    }

    element.zid = setTimeout(action, time);
}

// reloadUtil
// Reload our util frame
// IE issue fixed by Spocky, we have to use the iframe for Democratic Play &
// Localplay, which don't actually prompt for a new file
function reloadUtil(target) {
    $('#util_iframe').prop('src', target);
}

function reloadDivUtil(target) {
    var $util = $("#util_div");
    $.get(target, function (data, status, xhr) {
        var $response = $(data);
        $util.empty().append($response);
    });
}

// reloadRedirect
// Send them elsewhere
function reloadRedirect(target) {
    window.location = target;
}


window._ = require('lodash');

/**
 * We'll load jQuery and the Bootstrap jQuery plugin which provides support
 * for JavaScript based Bootstrap features such as modals and tabs. This
 * code may be modified to fit the specific needs of your application.
 */

try {
    window.$ = window.jQuery = require('jquery');

    require('bootstrap-sass');
} catch (e) {}

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

window.axios = require('axios');

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Next we will register the CSRF Token as a common header with Axios so that
 * all outgoing HTTP requests automatically have it attached. This is just
 * a simple convenience so we don't have to attach every token manually.
 */

let token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// import Echo from 'laravel-echo'

// window.Pusher = require('pusher-js');

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: 'your-pusher-key'
// });

function loadContentData(data, status, jqXHR)
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
		$("a[rel^='prettyPhoto']").prettyPhoto({social_tools:false});
		initTabs();
	}
}

function loadContentPage(url)
{
    var $mainContent = $("#content");

    $mainContent
        .find("#guts")
        .fadeOut(200, function() {
            $.get(url, function (data, status, jqXHR) {
                loadContentData(data, status, jqXHR);
            }, 'html');
        });
}

var sseSource = null;
function sse_worker(url) {
	if(typeof(EventSource) !== "undefined") {
		sseSource = new EventSource(url);
		sseSource.onmessage = function(event) {
			eval(event.data);
		};
		sseSource.onopen = function() {
			displayNotification('Connected through Server-Sent Events, processing...', 5000);
		};
		sseSource.onerror = function() {
			displayNotification('Server-Sent Events connection error. Re-connection...', 5000);
		};
	} else {
		// Server-Sent Events not supported, call the update in ajax and the output result
		$.get(url + '&html=1', function (data) {
			$("#guts").append(data);
		}, 'html')
	}
}

function stop_sse_worker() {
	if (sseSource !== null) {
		sseSource.close();
		sseSource = null;
	}
}

function display_sse_error(error) {
	displayNotification('ERROR: ' + error, 10000);
}

function NavigateTo(url)
{
    window.location.hash = url.substring(jsWebPath.length + 1);
}

function getCurrentPage()
{
    if (window.location.hash.length > 0) {
        var wpage = window.location.hash.substring(1);
        if (wpage !== 'prettyPhoto') {
            return btoa(wpage);
        } else {
            return "";
        }
    }

    return btoa(window.location.href.substring(jsWebPath.length + 1));
}

$(function() {

    var newHash      = "";
    
    $("body").delegate("a", "click", function() {
        var link = $(this).attr("href");
        if (link !== undefined && link != "" && link.indexOf("javascript:") != 0 && link != "#" && link != undefined && $(this).attr("onclick") == undefined && $(this).attr("rel") != "nohtml" && $(this).attr("target") != "_blank") {
            if ($(this).attr("rel") != "prettyPhoto") {
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
        if ($(this).attr('name') !== 'login' && $(this).attr('name') !== 'export' && (!$file || !$file.val() || $file.val() === "") && ($(this).attr('target') === undefined || $(this).attr('target') === '')) {
            var postData = $(this).serializeArray();
            var formURL = $(this).attr("action");

			if (formURL.indexOf('javascript:') !== 0) {
				$.ajax(
				{
					url : formURL,
					type: "POST",
					data : postData,
					success:function(data, status, jqXHR)
					{
						loadContentData(data, status, jqXHR);
						window.location.hash = "";
					},
					error: function(jqXHR, status, errorThrown)
					{
						alert(errorThrown);
					}
				});

				e.preventDefault();
			}
        }
    });
    
    $(window).bind('hashchange', function(){
        newHash = window.location.hash.substring(1);
        if (newHash && newHash.indexOf("prettyPhoto") != 0) {
            loadContentPage(jsWebPath + '/' + newHash);
            return false;
        };
    });
    
    $(window).trigger('hashchange');

});
// vim:set softtabstop=4 shiftwidth=4 expandtab:
//
// Copyright 2010 - 2015 Ampache.org
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
    add: function (ruleType, operator, input, subtype) {
        if (typeof(ruleType) != 'string') {
            ruleType = 0;
        }
        else {
            jQuery.each(types, function(i) {
                if (types[i].name == ruleType) {
                    ruleType = i;
                    return false;
                }
            });
        }

        if (typeof(operator) != 'string') {
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

        var row = document.createElement('tr');
        var cells = new Array();
        for (var i = 0 ; i < 5 ; i++) {
            cells[i] = document.createElement('td');
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

        $('#searchtable').append(row);
        rowCount++;

        $(cells[3]).on('click', function(){if(rowCount > 1) { $(this).parent().remove(); rowCount--; }});

        rowIter++;
    },
    constructInput: function(ruleType, ruleNumber, input) {
        if (input === null || input === undefined) {
            input = '';
        }

        widget = types[ruleType].widget;

        var inputNode  = document.createElement(widget['0']);
        inputNode.id   = 'rule_' + ruleNumber + '_input';
        inputNode.name = 'rule_' + ruleNumber + '_input';

        switch(widget['0']) {
            case 'input':
                inputNode.setAttribute('type', widget['1']);
                inputNode.setAttribute('value', input);
				break;
            case 'select':
                jQuery.each(widget['1'], function(i) {
                    var option = document.createElement('option');
                    if ( isNaN(parseInt(widget['1'][i])) ) {
                        realvalue = i;
                    }
                    else {
                        realvalue = parseInt(widget['1'][i]);
                    }
                    if ( input == realvalue ) {
                        option.selected = true;
                    }
                    option.value = realvalue;
                    option.innerHTML = widget['1'][i];
                    inputNode.appendChild(option);
                });
				break;
			case 'subtypes':
				inputNode = document.createElement(widget[1][0]);
				inputNode.id = 'rule_' + ruleNumber + '_input';
				inputNode.name = 'rule_' + ruleNumber + '_input';
				inputNode.setAttribute('type', widget[1][1]);
				inputNode.setAttribute('value', input);
				break;
        }

        return inputNode;
    },
    constructOptions: function(ruleType, ruleNumber) {
        var optionsNode  = document.createElement('select');
        optionsNode.id   = 'rule_' + ruleNumber;
        optionsNode.name = 'rule_' + ruleNumber;

        jQuery.each(types, function(i) {
            var option = document.createElement('option');
            option.innerHTML = types[i].label;
            option.value = types[i].name;
            if ( i == ruleType ) {
                option.selected = true;
            }
            optionsNode.appendChild(option);
        });

        $(optionsNode).change(SearchRow.update);

        return optionsNode;
    },
    constructOperators: function(ruleType, ruleNumber, operator) {
        var operatorNode    = document.createElement('select');
        operatorNode.id        = 'rule_' + ruleNumber + '_operator';
        operatorNode.name    = 'rule_' + ruleNumber + '_operator';

        basetype = types[ruleType].type;
        operatorNode.className    = 'operator' + basetype;

        var opts = basetypes[basetype];
        jQuery.each(opts, function(i) {
            var option = document.createElement('option');
            option.innerHTML = opts[i].description;
            option.value = i;
            if (i == operator) {
                option.selected = true;
            }
            operatorNode.appendChild(option);
        });

        return operatorNode;
    },
    update: function() {
        var r_findID = /rule_(\d+)/;
        var targetID = r_findID.exec(this.id)[1];

        var operator = $('#rule_' + targetID + '_operator');
        if (operator.className != 'operator' + types[this.selectedIndex].type) {
            var operator_cell = operator.parent();
            operator.remove();
            operator_cell.append(SearchRow.constructOperators(this.selectedIndex, targetID));
        }

		var type = $(this).val();

		jQuery.each(types, function (index, value) {
			if (value.name == type) {
				type = value
				return false;
			}
		});

		if (type['widget'][0] == 'subtypes') {
			var $select = SearchRow.createSelect({
				name: 'rule_' + targetID + '_subtype'
			}, type['subtypes']);
			$(this).after($select);
		}
		else {
			$(this).closest('tr').find('select[name="subtype"]').remove();
		}

		var input = $('#rule_' + targetID + '_input');
        if (input.type == 'text') {
            var oldinput = input.value;
        }

        var input_cell = input.parent();
        input.remove();
        input_cell.append(SearchRow.constructInput(this.selectedIndex, targetID, oldinput));
    },
	createSelect: function (attributes, options, selected) {
		var $select = $('<select>');
		$.each(attributes, function (key, value) {
			$select.attr(key, value);
		});

		$.each(options, function (key, value) {
			$('<option>').attr('value', key).text(value).appendTo($select);
		});
		$select.val(selected);
		return $select;
	},
	createSubtypeOptions: function (ruleType, ruleNumber, subtype) {
		var type = types[ruleType];

		var input;
		if (type['widget'][0] == 'subtypes') {
			var $input = SearchRow.createSelect({
				name: 'rule_' + ruleNumber + '_subtype'
			}, type['subtypes'], subtype);
			return $input[0];
		}
	}
};

$.widget( "custom.catcomplete", $.ui.autocomplete, {
    _renderItem: function( ul, item ) {
            var itemhtml = "";
            if (item.link !== '') {
                itemhtml += "<a href='" + item.link + "'>";
            } else {
                itemhtml += "<a>";
            }
            if (item.image != '') {
                itemhtml += "<img src='" + item.image + "' class='searchart' />";
            }
            itemhtml += "<span class='searchitemtxt'>" + item.label + ((item.rels == '') ? "" : " - " + item.rels)  + "</span>";
            itemhtml += "</a>";

            return $( "<li class='ui-menu-item'>" )
                .data("ui-autocomplete-item", item)
                .append( itemhtml )
                .appendTo( ul );
    },
    _renderMenu: function( ul, items ) {
        var that = this, currentType = "";
        $.each( items, function( index, item ) {
            if (item.type != currentType) {
                ul.append( "<li class='ui-autocomplete-category'>" + item.type + "</li>" );
                currentType = item.type;
            }

            that._renderItem( ul, item );
        });
    }
});

$(function() {
    $( "#searchString" )
    // don't navigate away from the field on tab when selecting an item
        .bind( "keydown", function( event ) {
            if ( event.keyCode === $.ui.keyCode.TAB && $( this ).data( "ui-autocomplete" ).menu.active ) {
                event.preventDefault();
            }
        })
        .catcomplete({
            source: function( request, response ) {
                $.getJSON( jsAjaxUrl, {
                    page: 'search',
                    action: 'search',
                    target: $('#searchStringRule').val(),
                    search: request.term,
                    xoutput: 'json'
                }, response );
            },
            search: function() {
                // custom minLength
                if (this.value.length < 2) {
                    return false;
                }
            },
            focus: function() {
                // prevent value inserted on focus
                return false;
            },
            select: function( event, ui ) {
                if (ui.item != null) {
                    $(this).val(ui.item.value);
                }
                return false;
            }
        });
    }
);
var lastaction = new Date().getTime();
var refresh_slideshow_interval = 10;
var iSlideshow = null;
var tSlideshow = null;
function init_slideshow_check()
{
    if (refresh_slideshow_interval > 0) {
        if (tSlideshow != null) {
            clearTimeout(tSlideshow);
        }
        tSlideshow = window.setTimeout(function(){init_slideshow_refresh();}, refresh_slideshow_interval * 1000);
    }
}
function swap_slideshow()
{
    if (iSlideshow == null) {
        init_slideshow_refresh();
    } else {
        stop_slideshow();
    }
}
function init_slideshow_refresh()
{
    if ($("#webplayer").is(":visible")) {
        clearTimeout(tSlideshow);
        tSlideshow = null;

        $("#aslideshow").height($(document).height())
          .css({'display': 'inline'});

        iSlideshow = true;
        refresh_slideshow();
    }
}
function refresh_slideshow()
{
    if (iSlideshow != null) {
        ajaxPut(jsAjaxUrl + '?page=index&action=slideshow', '');
    } else {
        init_slideshow_check();
    }
}
function stop_slideshow()
{
    if (iSlideshow != null) {
        iSlideshow = null;
        $("#aslideshow").css({'display': 'none'});
    }
}
function update_action()
{
    lastaction = new Date().getTime();
    stop_slideshow();
    init_slideshow_check();
}
$(document).mousemove(function(e) {
    if (iSlideshow == null) {
        update_action();
    }
});
$(document).ready(function() {
    init_slideshow_check();
});
// vim:set softtabstop=4 shiftwidth=4 expandtab:
//
// Copyright 2001 - 2015 Ampache.org
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
//

InitTableData();

function InitTableData()
{
	if ($('.tabledata').mediaTable()) {
        ResponsiveElements.init();
        setTimeout(function() {
            $('.tabledata').mediaTable('analyze');
        }, 1);
    }
}
