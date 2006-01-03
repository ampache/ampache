/* The AJAX stuff */
var xmlHttp;
var requestType="";

function createXMLHttpRequest() {
   if (window.ActiveXObject) {
      xmlHttp = new ActiveXObject("Microsoft.XMLHTTP");
   }
   else if (window.XMLHttpRequest) {
      xmlHttp = new XMLHttpRequest();
   }
}

function startRequest(params) {
   createXMLHttpRequest();
   xmlHttp.onreadystatechange = handleStateChange;
   xmlHttp.open("GET", "server/ajax.server.php?"+params, true);
/*   xmlHttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded'); */
   xmlHttp.send(null);
}

function timestuff() {
alert ("mpd_elapsed ="+fmt_time(mpd_elapsed)+"; nowtime - starttime ="+fmt_time(Math.floor ((nowtime - starttime)/1000))+"; secondssinceloaded ="+fmt_time(secondssinceloaded));
}

function handleStateChange() {
   if (xmlHttp.readyState == 4) {
      if (xmlHttp.status == 200) {
/* alert ("responseText = " + xmlHttp.responseText);  */
         var props = xmlHttp.responseXML.getElementsByTagName("properties")[0].childNodes;

         for (var i=1; i< props.length; i++) {
            el = props[i];
/* alert ("i="+i+"; "+el.tagName); */

            switch (el.tagName) {
               case 'action' : break;
               case 'volume' :
                  var vol = el.firstChild.data;
                  document.getElementsByName ('volume').firstChild.data = vol;
                  break;
               case 'mpd_cur_track_pos' :
                  mpd_elapsed = Math.floor(el.firstChild.data);
/* alert ('mpd_elapsed ='+mpd_elapsed); */
               starttime = new Date();
               starttime=starttime.getTime()
                  break;

               case 'state' :
                  var new_state = el.firstChild.data;
/* alert ('state = '+new_state+'; mpd_state = '+mpd_state); */
                  if (mpd_state != new_state) {
                     document.getElementById (mpd_state+'_button').className = "";
                     document.getElementById (new_state+'_button').className = "selected_button";
                     mpd_state = new_state;
                     if (mpd_state == "stop" || mpd_state == "pause") { 
                        mpd_notstoppause = 0;
                        document.getElementById ('mpd_np').className = "nodisplay";
/*               turn off the now playing stuff */
                     } else 
                     {
                        mpd_notstoppause = 1;
                        document.getElementById ('mpd_np').className = "";
/*               turn on the now playing stuff */
                     } // end if else
                  } // end if 
                  break;
               case 'now_playing' :
                  mpd_song_length = el.getElementsByTagName ('songlength')[0].firstChild.data;
                  mpd_songid = Math.round(el.getElementsByTagName ('songid')[0].firstChild.data);
                  document.getElementById ('mpd_npinfo').firstChild.data =
                      1+mpd_songid + ". " +
                      el.getElementsByTagName ('songartist')[0].firstChild.data + " - " +
                      el.getElementsByTagName ('songtitle')[0].firstChild.data + " - " +
                      el.getElementsByTagName ('songalbum')[0].firstChild.data + " - " +
                      fmt_time(mpd_song_length);
                  break;
               default :
                  alert ('Unknown XML reply :"'+el.tagName+'"');
            } // end switch
         } // end for
      } 
      else
      { alert ('status = ' + xmlHttp.status); 
      } // end if status else
   } //end if ready status
}


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


