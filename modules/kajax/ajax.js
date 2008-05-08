// Copyright (c) Ampache.org
// All rights reserved.
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
//
// uid is an array of uids that need to be replaced		
function ajaxPut(url,source) {

	if (document.getElementById(source)) { 
		Event.stopObserving(source,'click',function(){ajaxPut(url,source);});
	} 

	if (window.ActiveXObject) { // IE
		try {
			http_request = new ActiveXObject("Msxml2.XMLHTTP");
		} 
		catch (e) {
                	try {
       			        http_request = new ActiveXObject("Microsoft.XMLHTTP");
                	} 
			catch (e) {}
		}
        }
	else { // Mozilla
		IE = false;
		http_request = new XMLHttpRequest();	
	}
        if (!http_request) {
            return false;
        }
        http_request.onreadystatechange = function() { getContents(http_request); };
        http_request.open('GET', url, true);
       	http_request.send(null);	
}

// uid is an array of uids that need to be replaced             
function ajaxState(url,input) {

        var data = document.getElementById(input).value

        var post_data = input + '=' + encodeURI(data); 

        var http_request = false;
        if (window.XMLHttpRequest) { // Mozilla, Safari,...
                http_request = new XMLHttpRequest();
        } else if (window.ActiveXObject) { // IE
                try {
                        http_request = new ActiveXObject("Msxml2.XMLHTTP");
                } catch (e) {
                try {
                        http_request = new ActiveXObject("Microsoft.XMLHTTP");
                } catch (e) {}
                }
        }
        if (!http_request) {
                return false;
        }
        http_request.onreadystatechange = function() { getContents(http_request); };
        http_request.open('POST', url, true);
        http_request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        http_request.setRequestHeader("Content-length", post_data.length);
        http_request.setRequestHeader("Connection", "close");
        http_request.send(post_data);

} // ajaxState


///
// getContents
// This is the nitty gritty wait for a good xml document and then decode it
function getContents(http_request) {
	
	// Display the loading doodly
	document.getElementById('ajax-loading').style.display = 'block';

	if (http_request.readyState == 4) {
		if (http_request.status == 200) {
			var data = http_request.responseXML;
			var newContent = http_request.responseXML.getElementsByTagName('content'); 
	               

			for(var i=0; i < newContent.length; i++) {  
				var newID = newContent[i].getAttribute('div'); 				
				if (document.getElementById(newID)) { 
					$(newID).update(newContent[i].firstChild.nodeValue); 
				} 
			}
			document.getElementById('ajax-loading').style.display = 'none';
		} 
        }
}

function ajaxPost(url,input,source) { 
    
	if (document.getElementById(source)) { 
		Event.stopObserving(source,'click',function(){ajaxPost(url,input,source);}); 
	} 

        var post_data = 'a=0';
	var data = document.getElementById(input).elements;

	// For the post data we recieved
	for(i=0;i<data.length;i++) { 
		var frm_field = data[i];

		// This makes the value of the checkbox the checked status, more usefull
		if (frm_field.type == 'checkbox') { 
			frm_field.value = frm_field.checked; 
		} 
		post_data = post_data +'&' + frm_field.name + '=' + encodeURI(frm_field.value);
	}
	
	var http_request = false;
	if (window.XMLHttpRequest) { // Mozilla, Safari,...
        	http_request = new XMLHttpRequest();
	} else if (window.ActiveXObject) { // IE
        	try {
	        	http_request = new ActiveXObject("Msxml2.XMLHTTP");
	        } catch (e) {
	        try {
		        http_request = new ActiveXObject("Microsoft.XMLHTTP");
	        } catch (e) {}
	        }
	}
	if (!http_request) {
		return false;
	}
	http_request.onreadystatechange = function() { getContents(http_request); };
	http_request.open('POST', url, true);
	http_request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	http_request.setRequestHeader("Content-length", post_data.length);
	http_request.setRequestHeader("Connection", "close");
	http_request.send(post_data);

} 

