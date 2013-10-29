// vim:set softtabstop=4 shiftwidth=4 expandtab:
//
// Copyright 2001 - 2013 Ampache.org
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

// flipField
// Toggles the disabled property on the specifed field
function flipField(field) {
        if ($(field).disabled == false) {
                $(field).disabled = true;
        }
        else {
                $(field).disabled = false;
        }
} // flipField

// updateText
// Changes the specified elements innards. Used for the catalog mojo fluff.
function updateText(field, value) { 
    $(field).innerHTML = value;
} // updateText

// toggleVisible
// Toggles display type between block and none. Used for ajax loading div.
function toggleVisible(element) { 

    if ($(element).style.display == 'block') { 
        $(element).style.display = 'none';
    }
    else { 
        $(element).style.display = 'block'; 
    } 
} // toggleVisible

// delayRun
// This function delays the run of another function by X milliseconds
function delayRun(element, time, method, page, source) { 

    var function_string = method + '(\'' + page + '\',\'' + source + '\')'; 

    var action = function () { eval(function_string); }; 

    if (element.zid) { 
        clearTimeout(element.zid); 
    }

    element.zid = setTimeout(action, time); 

} // delayRun

// reloadUtil
// Reload our util frame
// IE issue fixed by Spocky, we have to use the iframe for Democratic Play & 
// Localplay, which don't actually prompt for a new file
function reloadUtil(target) { 

    if (navigator.appName == 'Opera') { 
        $('util_iframe').contentWindow.location.reload(true);    
    } 
    else if (navigator.appName == 'Konqueror') { 
        $('util_iframe').contentDocument.location.reload(true);
    } 
    else { 
        $('util_iframe').src = $('util_iframe').src;
    } 
} // reloadUtil

// reloadRedirect
// Send them elsewhere
function reloadRedirect(target) { 
    window.location = target;
} // reloadRedirect

// This is kind of ugly.  Let's not mess with it too much.
function check_inline_song_edit(type, song) {
        var target = type + '_select_' + song;
    if ($(target).options[$(target).selectedIndex].value == -1) {
        $(target).innerHTML = '<input type="textbox" name="' + type + '_name" value="New ' + type + '" />';
    }
}

