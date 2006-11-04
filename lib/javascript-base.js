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
  var checkflag_song = "false";

  function check_songs() {
        if (checkflag_song == "false") {
                if (document.forms.songs.elements["song[]"].length == undefined) {
                        document.forms.songs.elements["song[]"].checked = true;
                }
                else {
                        for (i = 0; i < document.forms.songs.elements["song[]"].length; i++) {
                                document.forms.songs.elements["song[]"][i].checked = true;
                        }
                }
                checkflag_song = "true";
                return "Unselect All";
        }
        else {
                if (document.forms.songs.elements["song[]"].length == undefined) {
                        document.forms.songs.elements["song[]"].checked = false;
                }
                else {
                        for (i = 0; i < document.forms.songs.elements["song[]"].length; i++) {
                                document.forms.songs.elements["song[]"][i].checked = false;
                        }
                }
                checkflag_song = "false";
                return "Select All";
        }
  }

        function invert_songs() {
                for( i = 0; i < document.forms.songs.elements["song[]"].length; ++i ) {
                        document.forms.songs.elements["song[]"][i].checked = !document.forms.songs.elements["song[]"][i].checked
                }
        }

  var checkflag_results = "false";

  function check_results() {
        if (checkflag_results == "false") {
                if (document.results.elements["results[]"].length == undefined) {
                        document.results.elements["results[]"].checked = true;
                }
                else {
                        for (i = 0; i < document.results.elements["results[]"].length; i++) {
                                document.results.elements["results[]"][i].checked = true;
                        }
                }
                checkflag_results = "true";
                return "Unselect All";
        }
        else {
                if (document.results.elements["results[]"].length == undefined) {
                        document.results.elements["results[]"].checked = false;
                }
                else {
                        for (i = 0; i < document.results.elements["results[]"].length; i++) {
                                document.results.elements["results[]"][i].checked = false;
                        }
                }
                checkflag_results = "false";
                return "Select All";
        }
}

// function for the catalog mojo fluf
function update_txt(value,field) { 
	document.getElementById(field).innerHTML=value;
}

// SubmitToPage this function specificaly submits the form to the specified page
function SubmitToPage(form_id,action) { 

	document.getElementById(form_id).action = action;
	document.getElementById(form_id).submit();
	return true;
} 

function popup_art(url) {
        var newwindow;
        newwindow=window.open(url, "ampache_art", "menubar=no,toolbar=no,location=no,directories=no");
        if (window.focus) {newwindow.focus()}
}

// function needed for IE.  attaches mouseover/out events to give/remove css class .sfhover (fake hover)
sfHover = function(navlist) {
var sfEls = document.getElementById("navlist").getElementsByTagName("LI");
for (var i=0; i <sfEls.length; i++) {
    sfEls[i].onmouseover=function() {
        this.className+=" sfhover";
    }           
    sfEls[i].onmouseout=function() {
        this.className=this.className.replace(new RegExp("sfhover\\b"), "");
    }           
} // end for    
} // end function for sfHover

if (window.attachEvent) window.attachEvent("onload", sfHover);


