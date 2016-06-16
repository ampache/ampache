// vim:set softtabstop=4 shiftwidth=4 expandtab:
//
// Copyright 2001 - 2015 Ampache.org
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License v2
// as published by the Free Software Foundation.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

// Some cutesy flashing thing while we run
$(document).ajaxSend(function () {
    $('#ajax-loading').show();
});
$(document).ajaxComplete(function () {
    $('#ajax-loading').hide();
});

// ajaxPost
// Post the contents of a form.
function ajaxPost(url, input, source) {
    if ($(source)) {
        $(source).off('click');
    } 
    $.ajax(url, { success: processContents, type: 'post', data: $('#'+input).serialize() });
} // ajaxPost

// ajaxPut
// Get response from the specified URL.
function ajaxPut(url, source) {
    if ($(source)) {
        $(source).off('click');
    }
    $.ajax(url, { success: processContents, type: 'get', dataType: 'xml' });
} // ajaxPut

// ajaxState
// Post the contents of a form without doing any observe() things.
function ajaxState(url, input) {
    $.ajax({
        url     : url,
        type    : 'POST',
        data    : $('#' + input).serialize(true),
        success : processContents
     });
} // ajaxState

// processContents
// Iterate over a response and do any updates we received.
function processContents(data) {
    $(data).find('content').each(function () {
        $('#' + $(this).attr('div')).html($(this).text());
    });
} // processContents


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
'use strict';

jPlayerPlaylist.prototype._createListItem = function(media) {
        var self = this;

        // Wrap the <li> contents in a <div>
        var attrClass = ' class="playlist-row ';
        if ('string' === typeof media.attrClass) {
            attrClass += media.attrClass;
        }
        attrClass += '" ';

        var listItem = "<li" + attrClass + " name=\"" + $(jplaylist.cssSelector.playlist + " ul li").length + "\" " +
				"data-poster=\"" + media['poster'] + "\" data-media_id=\"" + media['media_id'] + "\" data-album_id=\"" + media['album_id'] + "\" data-artist_id=\"" + media['artist_id'] + "\" " +
				"data-replaygain_track_gain=\"" + media['replaygain_track_gain'] + "\" data-replaygain_track_peak=\"" + media['replaygain_track_peak'] + "\" data-replaygain_album_gain=\"" + media['replaygain_album_gain'] + "\" data-replaygain_album_peak=\"" + media['replaygain_album_peak'] + "\"" +
				"><div>";

        // Create image
        // listItem += "<img class=\"cover\" src=\"" + media.cover + "\" alt=\"" + media.title + "\"/>";

        // Create remove control
        listItem += "<a href='javascript:;' class='" + this.options.playlistOptions.removeItemClass + "'>&times;</a>";

        // Create song links
        if (media['media_id']) {
            listItem += "<span class=\"jp-free-media menu\"><a></a></span>";
        }

        // The title is given next in the HTML otherwise the float:right on the free media corrupts in IE6/7
        listItem += "<a href='javascript:;' class='" + this.options.playlistOptions.itemClass + "' tabindex='1'>" + media.title + (media.artist ? " <span class='jp-artist'>by " + media.artist + "</span>" : "") + "</a>";
        listItem += "</div></li>";

        return listItem;
};

// TODO: take away the playlistName
jPlayerPlaylist.prototype.rmTrack = function(trackId, playlistName) {
    playlistName = playlistName || 'default';
    // $.bootstrapMessageLoading();
    $.post('/playlist/rmtrack', {
        playlist: playlistName,
        trackId: trackId
    }, function (data) {
        // $.bootstrapMessageAuto(data[0], data[1]);
        if ('error' === data[1]) {
            $.loadPlaylist();
        }
    }, 'json').error(function (e) {
        $.bootstrapMessageAuto('An error occured while trying to remove the track from your playlist.', 'error');
    });
}


jPlayerPlaylist.prototype.remove = function(index) {
    var self = this;

    console.log("remove track " + index);

    if (index === undefined) {
        this._initPlaylist([]);
        this._refresh(function() {
            $(self.cssSelector.jPlayer).jPlayer("clearMedia");
            self.scan();
        });
        return true;
    } else {

        if (this.removing) {
            return false;
        } else {
            index = (index < 0) ? self.original.length + index : index; // Negative index relates to end of array.
            if (0 <= index && index < this.playlist.length) {
                this.removing = true;

                if ('playlist' === jplaylist.type) {
                    console.log('delete track index ' + index);
                    var trackId = $($('.jp-playlist-item-remove')[index]).parent().parent().attr('track_id')
                    jplaylist.rmTrack(trackId, jplaylist.name);
                }

                $(this.cssSelector.playlist + " li:nth-child(" + (index + 1) + ")").slideUp(this.options.playlistOptions.removeTime, function() {
                    $(this).remove();

                    if (self.shuffled) {
                        var item = self.playlist[index];
                        $.each(self.original, function(i,v) {
                            if (self.original[i] === item) {
                                self.original.splice(i, 1);
                                return false; // Exit $.each
                            }
                        });
                        self.playlist.splice(index, 1);
                    } else {
                        self.original.splice(index, 1);
                        self.playlist.splice(index, 1);
                    }

                    if (self.original.length) {
                        if (index === self.current) {
                            self.current = (index < self.original.length) ? self.current : self.original.length - 1; // To cope when last element being selected when it was removed
                            self.select(self.current);
                        } else if (index < self.current) {
                            self.current--;
                        }
                    } else {
                        $(self.cssSelector.jPlayer).jPlayer("clearMedia");
                        self.current = 0;
                        self.shuffled = false;
                        self._updateControls();
                    }

                    self.removing = false;
                    self.scan();
                });
            }
            return true;
        }
    }
};

jPlayerPlaylist.prototype.removeAll = function() {
    this.original = [];
    this._originalPlaylist();
    $(this.cssSelector.playlist + " ul").html(' ');
};

jPlayerPlaylist.prototype.scan = function() {
    var self = this;
    var isAdjusted = false;

    var replace = [];
    var maxName = 0; // maximum value that name attribute assumes.
    $.each($(this.cssSelector.playlist + " ul li"), function(index, value) {
        if ($(value).attr('name') > maxName)
            maxName = parseInt($(value).attr('name'));
    });

    var diffCount = maxName + 1 != $(this.cssSelector.playlist + " ul li").length; // Flag that marks if the number of "ul li" elements doesn't match the name attribute counting.

    $.each($(this.cssSelector.playlist + " ul li"), function(index, value) {
        if (!diffCount) {
            replace[index] = self.original[$(value).attr('name')];
            if (!isAdjusted && self.current === parseInt($(value).attr('name'), 10)) {
                self.current = index;
                isAdjusted = true;
            }
        }
        $(value).attr('name', index);
    });

    if (!diffCount) {
        this.original = replace;
        this._originalPlaylist();
    }
};


jPlayerPlaylist.prototype.setCurrent = function(current) {
    this.current = current;
    this.select(this.current);
};

jPlayerPlaylist.prototype.play = function(index) {
    index = (index < 0) ? this.original.length + index : index; // Negative index relates to end of array.

    if ('function' === typeof this.options.callbackPlay) {
        this.options.callbackPlay(index);
    }

    if (0 <= index && index < this.playlist.length) {
        if (this.playlist.length) {
            this.select(index);
            $(this.cssSelector.jPlayer).jPlayer("play");
        }
    } else if (index === undefined) {
        $(this.cssSelector.jPlayer).jPlayer("play");
    }
};

jPlayerPlaylist.prototype._highlight = function(index) {
    $(this.cssSelector.title + " li:first").html('Playlist: ' + this.name);
    $(this.cssSelector.title + " li:last").html(' ');
    if (this.playlist.length && index !== undefined) {
        $(this.cssSelector.playlist + " .jp-playlist-current").removeClass("jp-playlist-current");
        $(this.cssSelector.playlist + " li:nth-child(" + (index + 1) + ")").addClass("jp-playlist-current").find(".jp-playlist-item").addClass("jp-playlist-current");
        $(this.cssSelector.title + " li:last").html(this.playlist[index].title + (this.playlist[index].artist ? " <span class='jp-artist'>by " + this.playlist[index].artist + "</span>" : ""));
    }
};

jPlayerPlaylist.prototype.addAfter = function(media, idx) {
    if (idx >= this.original.length || idx < 0) {
        console.log("jPlayerPlaylist.addAfter: ERROR, Index out of bounds");
        return;
    }

    $(this.cssSelector.playlist + " ul")
        .find("li[name=" + idx + "]").after(this._createListItem(media)).end()
        .find("li:last-child").hide().slideDown(this.options.playlistOptions.addTime);

    this._updateControls();
    this.original.splice(idx + 1, 0, media);
    this.playlist.splice(idx + 1, 0, media);

    if(this.original.length === 1) {
        this.select(0);
    }
};

$(document).ready(function() {
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

/***********/
/* Filters */
/***********/

function showFilters(element) {
    var link = $('.browse-options-link');
    link.hide();
    var content = $('.browse-options-content');
    content.show();
}

/************************************************************/
/* Dialog selection to add song to an existing/new playlist */
/************************************************************/

var closeplaylist;
function showPlaylistDialog(e, item_type, item_ids) {
    $("#playlistdialog").dialog("close");

    var parent = this;
    parent.itemType = item_type;
    parent.contentUrl = jsAjaxServer + '/edit.server.php?action=show_edit_playlist&object_type=' + item_type + '&id=' + item_ids;
    parent.editDialogId = '<div id="playlistdialog"></div>';

    $(parent.editDialogId).dialog({
        modal: false,
        dialogClass: 'playlistdialogstyle',
        resizable: false,
        draggable: false,
        width: 300,
        height: 100,
        autoOpen: false,
		position: {
			my: "left+10 top",
			of: e
		},
        open: function () {
            closeplaylist = 1;
            $(document).bind('click', overlayclickclose);
            $(this).load(parent.contentUrl, function() {
                $('#playlistdialog').focus();
            });
        },
        focus: function() {
            closeplaylist = 0;
        },
        close: function (e) {
            $(document).unbind('click');
            $(this).empty();
            $(this).dialog("destroy");
        }
    });

    $("#playlistdialog").dialog("open");
    closeplaylist = 0;
}

function overlayclickclose() {
    if (closeplaylist) {
        $("#playlistdialog").dialog("close");
    }
    closeplaylist = 1;
}

function handlePlaylistAction(url, id) {
    ajaxPut(url, id);
    $("#playlistdialog").dialog("close");
}

function createNewPlaylist(title, url, id) {
    var plname = window.prompt(title, '');
    if (plname != null) {
        url += '&name=' + plname;
        handlePlaylistAction(url, id);
    }
}

/************************************************************/
/* Dialog selection to start a broadcast */
/************************************************************/

var closebroadcasts;
function showBroadcastsDialog(e) {
    $("#broadcastsdialog").dialog("close");

    var parent = this;
    parent.contentUrl = jsAjaxServer + '/ajax.server.php?page=player&action=show_broadcasts';
    parent.editDialogId = '<div id="broadcastsdialog"></div>';

    $(parent.editDialogId).dialog({
        modal: false,
        dialogClass: 'broadcastsdialogstyle',
        resizable: false,
        draggable: false,
        width: 150,
        height: 70,
        autoOpen: false,
		position: {
			my: "left-180 top",
			of: e
		},
        open: function () {
            closebroadcasts = 1;
            $(document).bind('click', broverlayclickclose);
            $(this).load(parent.contentUrl, function() {
                $('#broadcastsdialog').focus();
            });
        },
        focus: function() {
            closebroadcasts = 0;
        },
        close: function (e) {
            $(document).unbind('click');
            $(this).empty();
            $(this).dialog("destroy");
        }
    });

    $("#broadcastsdialog").dialog("open");
    closebroadcasts = 0;
}

function broverlayclickclose() {
    if (closebroadcasts) {
        $("#broadcastsdialog").dialog("close");
    }
    closebroadcasts = 1;
}

function handleBroadcastAction(url, id) {
    ajaxPut(url, id);
    $("#broadcastsdialog").dialog("close");
}

/************************************************************/
/* Dialog selection to start a broadcast */
/************************************************************/

var closeshare;
function showShareDialog(e, object_type, object_id) {
    $("#sharedialog").dialog("close");

    var parent = this;
    parent.contentUrl = jsAjaxServer + '/ajax.server.php?page=browse&action=get_share_links&object_type=' + object_type + '&object_id=' + object_id;
    parent.editDialogId = '<div id="sharedialog"></div>';

    $(parent.editDialogId).dialog({
        modal: false,
        dialogClass: 'sharedialogstyle',
        resizable: false,
        draggable: false,
        width: 200,
        height: 90,
        autoOpen: false,
		position: {
			my: "left+10 top",
			of: e
		},
        open: function () {
            closeshare = 1;
            $(document).bind('click', shoverlayclickclose);
            $(this).load(parent.contentUrl, function() {
                $('#sharedialog').focus();
            });
        },
        focus: function() {
            closeshare = 0;
        },
        close: function (e) {
            $(document).unbind('click');
            $(this).empty();
            $(this).dialog("destroy");
        }
    });

    $("#sharedialog").dialog("open");
    closeshare = 0;
}

function shoverlayclickclose(e) {
	if (closeshare) {
		$("#sharedialog").dialog("close");
	}
	closeshare = 1;
}

function handleShareAction(url) {
    window.open(url);
    $("#sharedialog").dialog("close");
}


/***************************************************/
/* Edit modal dialog for artists, albums and songs */
/***************************************************/

var tag_choices = undefined;
var label_choices = undefined;

function showEditDialog(edit_type, edit_id, edit_form_id, edit_title, refresh_row_prefix) {
    var parent = this;
    parent.editFormId = 'form#' + edit_form_id;
    parent.contentUrl = jsAjaxServer + '/edit.server.php?action=show_edit_object&id=' + edit_id + '&type=' + edit_type;
    parent.saveUrl = jsAjaxServer + '/edit.server.php?action=edit_object&id=' + edit_id + '&type=' + edit_type;
    parent.editDialogId = '<div id="editdialog"></div>';
    parent.refreshRowPrefix = refresh_row_prefix;
    parent.editType = edit_type;
    parent.editId = edit_id;

    // Convert choices string ("tag0,tag1,tag2,...") to choices array
    parent.editTagChoices = new Array();
    if (tag_choices == undefined && tag_choices != '') {
        // Load tag map
        $.ajax(jsAjaxServer + '/ajax.server.php?page=tag&action=get_tag_map', {
            success: function(data) {
                tag_choices = $(data).find('content').text();
                if (tag_choices != '') {
                    showEditDialog(edit_type, edit_id, edit_form_id, edit_title, refresh_row_prefix);
                }
            }, type: 'post', dataType: 'xml'
        });
        return;
    }
	parent.editLabelChoices = new Array();
    if (label_choices == undefined && label_choices != '') {
        // Load tag map
        $.ajax(jsAjaxServer + '/ajax.server.php?page=tag&action=get_labels', {
            success: function(data) {
                label_choices = $(data).find('content').text();
                if (label_choices != '') {
                    showEditDialog(edit_type, edit_id, edit_form_id, edit_title, refresh_row_prefix);
                }
            }, type: 'post', dataType: 'xml'
        });
        return;
    }
    var splitted = tag_choices.split(',');
    var i;
    for (i = 0; i < splitted.length; ++i) {
        parent.editTagChoices.push($.trim(splitted[i]));
    }
	splitted = label_choices.split(',');
	for (i = 0; i < splitted.length; ++i) {
        parent.editLabelChoices.push($.trim(splitted[i]));
    }

    parent.dialog_buttons = {};
    this.dialog_buttons[jsSaveTitle] = function () {
        $.ajax({
            url: parent.saveUrl,
            type: 'POST',
            data: $(parent.editFormId).serializeArray(),
            success: function (resp) {
                $("#editdialog").dialog("close");

                if (parent.refreshRowPrefix != '') {
                    var new_id = $.trim(resp.lastChild.textContent);

                    // resp should contain the new identifier, otherwise we take the same as the edited item
                    if (new_id == '') {
                        new_id = parent.editId;
                    }

                    var url = jsAjaxServer + '/edit.server.php?action=refresh_updated&type=' + parent.editType + '&id=' + new_id;
                    // Reload only table
                    $('#' + parent.refreshRowPrefix + parent.editId).load(url, function() {
                        // Update the current row identifier with new id
                        $('#' + parent.refreshRowPrefix + parent.editId).attr("id", parent.refreshRowPrefix + new_id);
                    });
                } else {
                    var reloadp = window.location;
                    var hash = window.location.hash.substring(1);
                    if (hash && hash.indexOf('.php') > -1) {
                        reloadp = jsWebPath + '/' + hash;
                    }
                    loadContentPage(reloadp);
                }
            },
            error: function(resp) {
                $("#editdialog").dialog("close");
            }
        });
    }
    this.dialog_buttons[jsCancelTitle] = function() {
        $("#editdialog").dialog("close");
    }

    $(parent.editDialogId).dialog({
        title: edit_title,
        modal: true,
        dialogClass: 'editdialogstyle',
        resizable: false,
        width: 666,
        autoOpen: false,
        show: { effect: "fade", duration: 400 },
        open: function () {
            $(this).load(parent.contentUrl, function() {
                if ($('#edit_tags').length > 0) {
                    $("#edit_tags").tagit({
                        allowSpaces: true,
                        singleField: true,
                        singleFieldDelimiter: ',',
                        availableTags: parent.editTagChoices
                    });
                }
				if ($('#edit_labels').length > 0) {
                    $("#edit_labels").tagit({
                        allowSpaces: true,
                        singleField: true,
                        singleFieldDelimiter: ',',
                        availableTags: parent.editLabelChoices
                    });
                }
            });
        },
        close: function (e) {
            $(this).empty();
            $(this).dialog("destroy");
        },
        buttons: dialog_buttons
    });

    $("#editdialog").dialog("open");
}

$(window).resize(function() {
    $("#editdialog").dialog("option", "position", {my: "center", at: "center", of: window});
});

function check_inline_song_edit(type, song) {
    var source = '#' + type + '_select_' + song;
    if ($(source + ' option:selected').val() == -1) {
		$(source).fadeOut(600, function() {
			$(this).replaceWith('<input type="text" name="' + type + '_name" value="New ' + type + '" onclick="this.select();" />');
		});
    }
}

/*********************/
/*   Sortable table  */
/*********************/

function sortPlaylistRender() {
	var eles = $("tbody[id^='sortableplaylist_']");
    if (eles != null) {
        var len = eles.length;
        for (var i = 0; i < len; i++) {
            $('#' + eles[i].id).sortable({
                axis: 'y',
                delay: 200
            });
        }
    }
}

$(document).ready(function () {
	sortPlaylistRender();
});

function submitNewItemsOrder(itemId, tableid, rowPrefix, updateUrl, refreshAction) {
    var parent = this;
    parent.itemId = itemId;
    parent.refreshAction = refreshAction;

    var table = document.getElementById(tableid);
    var rowLength = table.rows.length;
	var offset = 0;
    var finalOrder = '';
	
	if ($('#' + tableid).attr('data-offset')) {
		offset = $('#' + tableid).attr('data-offset');
	}

    for (var i = 0; i < rowLength; ++i) {
        var row = table.rows[i];
        if (row.id != '') {
            var songid = row.id.replace(rowPrefix, '');
            finalOrder += songid + ";"
        }
    }

    if (finalOrder != '') {
        $.ajax({
            url: updateUrl,
            type: 'GET',
            data: 'offset=' + offset + '&order=' + finalOrder,
            success: function (resp) {
                var url = jsAjaxServer + '/refresh_reordered.server.php?action=' + parent.refreshAction + '&id=' + parent.itemId;
                // Reload only table
                $('#reordered_list_' + parent.itemId).load(url, function () {
                    $('#sortableplaylist_' + parent.itemId).sortable({
                        axis: 'y',
                        delay: 200
                    });
                });
            }
        });
    }
}

function getPagePlaySettings() {
    var settings = '';
    var stg_subtitle = document.getElementById('play_setting_subtitle');
    if (stg_subtitle !== undefined && stg_subtitle !== null) {
        if (stg_subtitle.value != '') {
            settings += '&subtitle=' + stg_subtitle.value;
        }
    }

    return settings;
}

function geolocate_user() {
    if(navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(geolocate_user_callback);
    } else {
        console.error("This browser does not support geolocation");
    }
}

function geolocate_user_callback(position) {
    var url = jsAjaxUrl + '?page=stats&action=geolocation&latitude=' + position.coords.latitude + '&longitude=' + position.coords.longitude;
    $.get(url);
}

function show_selected_license_link(license_select) {
    var license = $('#' + license_select + ' option:selected');
    var link = license.attr('data-link');
    if (link !== undefined) {
        window.open(link);
    }
}

//# sourceMappingURL=main.js.map
