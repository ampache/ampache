//
//
// Copyright (c) Ampache.org
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

function disableField(element) {
        var element_id = document.getElementById(element);
        element_id.disabled=true;
        element_id.value='';
        element_id.checked=false;
}
function enableField(element) {
        var element_id = document.getElementById(element);
        element_id.disabled=false;
        
}
function flipField(element) {
        var element_id = document.getElementById(element);
        if (element_id.disabled == false) {
                element_id.disabled=true;
        }
        else {
                element_id.disabled=false;
        }
}

function selectField(element) {
    var element_id = document.getElementById(element);
    element_id.focus();
}

// function for the catalog mojo fluf
function update_txt(value,field) { 
	document.getElementById(field).innerHTML=value;
}

// Function for enabling/disabling a div (display:none) used for loading div
function toggle_visible(element) { 

	var obj = document.getElementById(element);
	if (obj.style.display == 'block') { 
		obj.style.display = 'none';
	}
	else { 
		obj.style.display = 'block'; 
	} 
}

///
// DelayRun
// This function delays the run of another function by X milliseconds
function DelayRun(element,time,method,page,source) { 

	var function_string = method + '(\'' + page + '\',\'' + source + '\')'; 

	var action = function () { eval(function_string); }; 

	if (element.zid) { 
		clearTimeout(element.zid); 
	}

	element.zid = setTimeout(action,time); 

} // DelayRun


// Reload our util frame
// IE issue fixed by Spocky, we have to use the iframe for Democratic Play & Localplay
// which don't actually prompt for a new file
function reload_util(target) { 

	if (navigator.appName == 'Opera') { 
		document.getElementById('util_iframe').contentWindow.location.reload(true);	
	} 
	else if (navigator.appName == 'Konqueror') { 
		document.getElementById('util_iframe').contentDocument.location.reload(true);
	} 
	else { 
		document.getElementById('util_iframe').src = document.getElementById('util_iframe').src;
	} 
} 

// Log them out
function reload_logout(target) { 
	window.location = target;
}

function popup_art(url) {
        var newwindow;
        newwindow=window.open(url, "ampache_art", "menubar=no,toolbar=no,location=no,directories=no");
        if (window.focus) {newwindow.focus()}
}

function check_inline_song_edit(type, song) {
	if ($(type+'_select_'+song).options[$(type+'_select_'+song).selectedIndex].value == -1) {
		$(type+'_select_song_'+song).innerHTML = '<input type="textbox" name="'+type+'_name" value="New '+type+'" />';
	} else {
		$(type+'_select_song_'+song).innerHTML = '';
	}
}

