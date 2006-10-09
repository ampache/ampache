	//var xmlDoc = null;
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
