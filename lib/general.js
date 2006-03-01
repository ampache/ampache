/* The AJAX stuff */
var xmlHttp;
var requestType="";
var ret_songid=0;


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
   xmlHttp.open("GET", web_path + "/server/ajax.server.php?"+params+"&player="+player, true);
/*   xmlHttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded'); */
   xmlHttp.send(null);
}

function timestuff() {
alert ("mpd_elapsed ="+fmt_time(mpd_elapsed)+"; nowtime - starttime ="+fmt_time(Math.floor ((nowtime - starttime)/1000))+"; secondssinceloaded ="+fmt_time(secondssinceloaded));
}

function handleStateChange() {
   if (xmlHttp.readyState == 4) {
      if (xmlHttp.status == 200) {
/* alert ("responseText = " + xmlHttp.responseText);   */
         var props = xmlHttp.responseXML.getElementsByTagName("properties")[0].childNodes;

         for (var i=1; i< props.length; i++) {
            el = props[i];
/* alert ("i="+i+"; "+el.tagName); */

            switch (el.tagName) {
               case 'action' : break;
               case 'volume' :
                  var vol = el.firstChild.data;
                   if (player == 'mpd') document.getElementById ('volume').firstChild.data = vol;
                  break;
               case 'mpd_cur_track_pos' :
                  mpd_elapsed = Math.floor(el.firstChild.data);
/* alert ('mpd_elapsed ='+mpd_elapsed); */
               starttime = new Date();
               starttime=starttime.getTime()
                  break;

               case 'state' :
                  var new_state = el.firstChild.data;
/* alert ('state = '+new_state+'; player_state = '+player_state); */
                  if ((player == 'mpd') && (player_state != new_state)) {
                     document.getElementById (player_state+'_button').className = "";
                     document.getElementById (new_state+'_button').className = "selected_button";
                     player_state = new_state;
                     if (player_state == "stop" || player_state == "pause") { 
			if (document.getElementById ('mpd_np'))
			   document.getElementById ('mpd_np').className = "nodisplay";
                     } else 
                     {
			if (document.getElementById ('mpd_np'))
   			   document.getElementById ('mpd_np').className = "";
                     } // end if else
                  } // end if mpd changed player_state
                  break;
               case 'now_playing' :
      	                ret_songid = Math.round(el.getElementsByTagName ('songid')[0].firstChild.data);
			if (player == 'mpd' && player_state != 'stop') {
	                  mpd_song_length = el.getElementsByTagName ('songlength')[0].firstChild.data;
			  if (document.getElementById ('mpd_npinfo')) {
                  	     document.getElementById ('mpd_npinfo').firstChild.data =
	                         1+ret_songid + ". " +
	                         el.getElementsByTagName ('songartist')[0].firstChild.data + " - " +
	                         el.getElementsByTagName ('songtitle')[0].firstChild.data + " - " +
	                         el.getElementsByTagName ('songalbum')[0].firstChild.data + " - " +
	                         fmt_time(mpd_song_length);
			  }
			}  
			  if (ret_songid != mpd_songid) {
			      if (document.getElementById ('mpd_row'+mpd_songid)) {
				 if ((mpd_songid - mpdpl_first) %2 == 1) {
				    document.getElementById ('mpd_row'+mpd_songid).className = 'even';
				 } else {
				    document.getElementById ('mpd_row'+mpd_songid).className = 'odd';
				 }
			      }
			      if (player_state != 'stop') {

			         if ((document.getElementById ('mpd_row'+ret_songid) != null) && (player_state != 'stop')) {
				    document.getElementById ('mpd_row'+ret_songid).className = 'npsong';
			         }
	                      }

                                    mpd_songid = ret_songid;
			  }
                          break;
		       case 'now_playing_display' : 
			      // fix for pages where now playing data doesnt exist
                  if (document.getElementById('np_songid_0_holder')) {
				      show_now_playing_display(el);
                  }
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


// the actual function that checks and updates the now playing data.
function show_now_playing_display (el) {
  for (var i=0; i<el.childNodes.length; i++) {
    now_playing = el.childNodes[i];
	
	// check if we need to update
	if (document.getElementById('np_songid_'+i+'_holder').innerHTML ==
		now_playing.getElementsByTagName('songid')[0].firstChild.data) { } else {
		
	  // set the songid holder, so we only update if nessicary... (no album art flashing)
	  document.getElementById('np_songid_'+i+'_holder').innerHTML = 
	    now_playing.getElementsByTagName('songid')[0].firstChild.data;
		
	  // output the fullname of the person, may be blank
	  document.getElementById('np_fullname_'+i).innerHTML = 
	    now_playing.getElementsByTagName('fullname')[0].firstChild.data;
		
	  // output the song name and link tag
	  document.getElementById('np_song_'+i).innerHTML =
	    '<a href="song.php?action=m3u&amp;song=' +
		now_playing.getElementsByTagName('songid')[0].firstChild.data + '">' +
		now_playing.getElementsByTagName('songtitle')[0].firstChild.data + '</a>';
		
		
	  // output the artist / album and link tags
	  document.getElementById('np_albumartist_'+i).innerHTML = 
		'<a href="albums.php?action=show&amp;album=' + 
		now_playing.getElementsByTagName('albumid')[0].firstChild.data + '">' + 
		now_playing.getElementsByTagName('songalbum')[0].firstChild.data + 
		'</a> / <a href="artists.php?action=show&amp;artist=' + 
		now_playing.getElementsByTagName('artistid')[0].firstChild.data + '">' + 
		now_playing.getElementsByTagName('songartist')[0].firstChild.data + '</a>';
	  
	  // output the album art, and the link for it
	  document.getElementById('np_img_'+i).innerHTML = 
  	    '<a target="_blank" href="albumart.php?id=' +
	    now_playing.getElementsByTagName('albumid')[0].firstChild.data +
	    '&amp;type=popup" onclick="popup_art(\'albumart.php?id=' +
	    now_playing.getElementsByTagName('albumid')[0].firstChild.data +
	    '&amp;type=popup\'); return false;">' + 
        '<img align="middle" border="0" src="albumart.php?id=' +
	    now_playing.getElementsByTagName('albumid')[0].firstChild.data +
	    '&amp;fast=1&amp;thumb=1" alt="Album Art" height="75" /></a>';
		
	  
	  // make sure its visible.
	  document.getElementById('np_container_'+i).style.display = 'block';
	  
		
	} // end if holder = songid
  } // for ecah record we get
  
  // fill in the rest with blank data and hide them.
  while (i<5) {
	  document.getElementById('np_container_'+i).style.display = 'none';
	  document.getElementById('np_songid_'+i+'_holder').innerHTML =  '';
	  i++;
  } // end while i<5
  
} // end show_now_playing_display function


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


