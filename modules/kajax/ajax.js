// Copyright Ampache.org 2001 - 2006
// All Rights Reserved
// Origional Author: Kevin Riker
// Added Multi-Value XML based GET/POST replacement * Karl Vollmer
// Added Auto-Detects source/target information based on XML Doc Elements and
// 	 Form Elements if it's a post call * Karl Vollmer
// Licensed under the GNU/GPL
	
	var http_request = false;
	var IE = true;
	
        function ajaxRequest(url) {
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
        http_request.onreadystatechange = function() { };
        http_request.open('GET', url, true);
        http_request.send(null);
	}
	
	// uid is an array of uids that need to be replaced		
	function ajaxPut(url) {
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

    function getContents(http_request,uid) {
        if (http_request.readyState == 4) {
	    	data = http_request.responseXML;
		for(i=0;i<data.childNodes[0].childNodes.length;i++) {
			if (data.childNodes[0].childNodes[i].nodeType == '1') { 
				var txt_node = data.childNodes[0].childNodes[i];
				var new_txt = txt_node.firstChild.nodeValue;
				document.getElementById(txt_node.localName).innerHTML = new_txt;
			}
		}	
        }
    }

    function ajaxPost(url,input) { 
    
        var post_data = 'a=0';
	var data = document.getElementById(input).elements;

	for(i=0;i<data.length;i++) { 
		var frm_field = data[i];
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
