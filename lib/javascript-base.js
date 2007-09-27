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

var checkflag = "false";

function check_select(type,name) {
	if ( name == undefined){
	    var name = '';
	} 
	if ( checkflag == "false") {
                if ( eval("document.forms."+ type +"s"+ name +".elements[\""+ type +"[]\"].length") == undefined) {
			var zz = eval("document.forms."+ type +"s"+ name +".elements[\""+ type +"[]\"]");
			zz.checked = true;			
                }
                else {
                        for (i = 0; i < eval("document.forms."+ type +"s"+ name +".elements[\""+ type +"[]\"].length"); i++) {
			var zz = eval("document.forms."+ type +"s"+ name +".elements[\""+ type +"[]\"][i]");
			zz.checked = true;
                        }
                }
                checkflag = "true";
                return "Unselect All";
        }
        else {
                if ( eval("document.forms."+ type +"s"+ name +".elements[\""+ type +"[]\"].length") == undefined) {
			var zz = eval("document.forms."+ type +"s"+ name +".elements[\""+ type +"[]\"]");			
			zz.checked = false;
                }
                else {
                        for (i = 0; i < eval("document.forms."+ type +"s"+ name +".elements[\""+ type +"[]\"].length"); i++) {
			var zz = eval("document.forms."+ type +"s"+ name +".elements[\""+ type +"[]\"][i]");
			zz.checked = false;
			}
                }
                checkflag = "false";
                return "Select All";
        }
}

// function for the catalog mojo fluf
function update_txt(value,field) { 
	document.getElementById(field).innerHTML=value;
}

// Reload our util frame
function reload_util() { 
	document.getElementById('util_iframe').contentWindow.location.reload(true);
} 

function popup_art(url) {
        var newwindow;
        newwindow=window.open(url, "ampache_art", "menubar=no,toolbar=no,location=no,directories=no");
        if (window.focus) {newwindow.focus()}
}
