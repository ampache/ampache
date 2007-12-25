//
//
// Copyright (c) 2001 - 2006 Ampache.org
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
function selectField(element)
{
    var element_id = document.getElementById(element);
    element_id.focus();
}

// function for the catalog mojo fluf
function update_txt(value,field) { 
	document.getElementById(field).innerHTML=value;
}

// Reload our util frame
// IE issue fixed by Spocky, we have to use the iframe for Democratic Play & Localplay
// which don't actually prompt for a new file
function reload_util(target) { 

	if (navigator.appName == 'Opera') { 
		document.getElementById('util_iframe').contentWindow.location.reload(true);	
	} 
	else if (navigator.appName == 'Konqueror') { 
		alert(document.getElementById('util_iframe').location.url);
		document.getElementById('util_iframe').location.url = document.getElementById('util_iframe').location.url
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

// In-line album editing helper function
function checkAlbum(song) {
	if ($('album_select_'+song).options[$('album_select_'+song).selectedIndex].value == -1) {
		$('album_select_song_'+song).innerHTML = '<input type="textbox" name="album_name" value="New Album" />';
	} else {
		$('album_select_song_'+song).innerHTML = '';
	}
}

