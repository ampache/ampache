// Copyright Ampache.org 2001 - 2006
// All Rights Reserved
// Origional Author: Kevin Riker
// Added Multi-Value XML based GET/POST replacement * Karl Vollmer
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
	function ajaxPut(url,uid) {
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
        http_request.onreadystatechange = function() { getContents(http_request,uid); };
        http_request.open('GET', url, true);
        http_request.send(null);
    }

    function getContents(http_request,uid) {
        if (http_request.readyState == 4) {
	    	data = http_request.responseXML;
		for(i=0;i<uid.length;i++) {
			var new_txt = data.getElementsByTagName(uid[i])[0].firstChild.nodeValue;	
			document.getElementById(uid[i]).innerHTML = new_txt;
		}	
        }
    }

    function ajaxPost(url,input,output) { 
    
        var post_data = 'a=0';
	
	for(i=0;i<input.length;i++) { 
		post_data = post_data +'&' + input[i] + '=' + encodeURI(document.getElementById(input[i]).value);
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
	http_request.onreadystatechange = function() { getContents(http_request,output); };
	http_request.open('POST', url, true);
	http_request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	http_request.setRequestHeader("Content-length", post_data.length);
	http_request.setRequestHeader("Connection", "close");
	http_request.send(post_data);


    } 
