<script type="text/javascript">
	//var xmlDoc = null;
	var http_request = false;
	var IE = true;
	
	function makeRequest(url,getTerms) {
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
        http_request.onreadystatechange = function() {};
        http_request.open('GET', url+"?"+getTerms, false);
        http_request.send(null);
    }

    function getContents(http_request) {
        if (http_request.readyState == 4) {
            if (http_request.status == 200) {
			
			}
        }
    }

    function ajaxPut(url,getTerms,uid) {
	makeRequest(url,getTerms);
	
	data = http_request.responseTXT;
	document.getElementById(uid).innerHTML = data;
    }

</script>
