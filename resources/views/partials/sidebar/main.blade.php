
@section('sidebar')

<!--Main Sidebar -->
<?php $isCollapsed = ((config('feature.sidebar_light') && $_COOKIE['sidebar_state'] != "expanded") || $_COOKIE['sidebar_state'] == "collapsed"); ?>
 <div id="sidebar" class="w3-sidebar w3-bar-block w3-theme-d4 w3-section" style="width:12%;min-width:165px;">
   <div id="sidebar-header" class="w3-center" style="cursor:pointer">
      <span id="sidebar-header-content">{{ $isCollapsed ? '>>>>' : '<<<<' }}</span>
   </div>
   @if (Auth::check())
     @if (!$_COOKIE['sidebar_tab']) {
       @php $_COOKIE['sidebar_tab'] = 'home'; @endphp
     @endif
     @php
        $class_name = 'w3_sidebar sidebar_' . $_COOKIE['sidebar_tab'];
     @endphp
     
     <div id="sidebar-tabs" class="w3-animate-left">
   		<div id="sidebar-row" class="w3-cell-row">
    		<div onclick="loadTab('home')" id="sb_tab_home" class="w3-container w3-cell w3-padding-small" title="home">
    		<i class="material-icons w3-small w3-hover-red" style="cursor:pointer">headset</i>
  		   </div>
   		<div onclick="loadTab('preferences')" id="sb_tab_preferences" class="w3-container w3-cell w3-padding-small" title="preferences">
    		<i class="material-icons w3-small w3-hover-red" style="cursor:pointer">settings</i>
  		</div>
  		<div onclick="loadTab('localplay')" id="sb_tab_localplay" class="w3-container w3-cell w3-padding-small" title="localplay">
   			<i class="material-icons w3-small w3-hover-red" style="cursor:pointer">speaker</i>
  		</div>
  		@role('Administrator')
   		    <div onclick="loadTab('modules')" id="sb_tab_modules" class="w3-container w3-cell w3-padding-small" title="modules">
    		    <i class="material-icons w3-small w3-hover-red" style="cursor:pointer">view_module</i>
  		    </div>
  		    <div onclick="loadTab('admin')"id="sb_tab_admin" class="w3-container w3-cell w3-padding-small" title="admin">
    		    <i class="material-icons w3-small w3-hover-red" style="cursor:pointer">supervisor_account</i>
  		    </div>
  		@endrole
  		<div  id="exit" class="w3-container w3-cell w3-padding-small" title="exit">
              <a href="{!! route('logout') !!}"
                 Logout
             </a>
    		<i class="material-icons w3-small w3-hover-red" style="cursor:pointer">exit_to_app</i>
  		</div> 
		</div>
	  </div>
    @endif
  <div id="sidebar-content" class="w3-animate-left {{ $isCollapsed ? 'w3-hide' : '' }}">
       	@include ('partials.sidebar.home')
</div>
<!-- // TODO Add guest authorization for favorites and upload capability -->
<ul id="sidebar-light" class="w3-animate-left w3-hide">
    <li><img src="{{ asset('images/topmenu-artist.png') }}" title="{{ 'Artists' }}" /><br />{{ 'Artists' }}</li>
    <li><img src="{{ asset('images/topmenu-album.png') }}" title="{{ 'Albums' }}" /><br />{{ 'Albums' }}</li>
    <li><img src="{{ asset('/images/topmenu-playlist.png') }}" title="{{ 'Playlists' }}" /><br />{{ 'Playlists' }}</li>
    <li><img src="{{ asset('images/topmenu-tagcloud.png') }}" title="{{ 'Tag Cloud' }}" /><br />{{ 'Tag Cloud' }}</li>
    @if (config('features.live_stream'))
        <li><img src="{{ url('images/topmenu-radio.png') }}" title="{{ 'Radio Stations' }}" /><br />{{ 'Radio' }}</li>
    @endif
    @if (config('feature.userflags')/* && (Auth::user()->isRegisteredUser()) */)
         <li><img src="{{ url('/images/topmenu-favorite.png') }}" title="{{ 'Favorites' }}" /><br />{{ 'Favorites' }}</li>
    @endif
    @if (config('feature.allow_upload') /* && (Auth::user()->isRegisteredUser()) */)
         <li><img src="{{ url('/images/topmenu-upload.png') }}" title="{{ 'Upload' }}" /><br />{{ 'Upload' }}</a></li>
    @endif
</ul>

<script>

function loadTab(tab) {
	var x = "{{ url('/loadtab') . "/" }}" + tab;
    if ($("#sidebar_" + tab).length == false) {
            $("#sidebar-content").load(x, function(responseTxt, statusTxt, xhr){
	            set_MenuItems();
	         });
      }
    setCookie('sidebar_tab', tab, 30);
}

$("#sidebar-header").click(function(){
    var newstate = "collapsed";
    if (getCookie("sidebar_state") == "collapsed") {
        newstate = "expanded";
    }

    $("#sidebar").addClass("w3-hide");
    var sidebar_content = document.getElementById("sidebar-content");
    var sidebar_light = document.getElementById("sidebar-light")
    if (newstate == "expanded") {
        $("#sidebar-content").addClass("w3-show").removeClass("w3-hide");
        $("#sidebar-light").addClass("w3-hide").removeClass("w3-show");
        $('#sidebar-header-content').text('<<<');
        $("#sidebar-tabs").addClass("w3-show").removeClass("w3-hide");
    } else {
        $("#sidebar-content").addClass("w3-hide").removeClass("w3-show");
        $("#sidebar-light").addClass("w3-show").removeClass("w3-hide");
        $("#sidebar-header-content").text('>>>');
        $("#sidebar-tabs").addClass("w3-hide").removeClass("w3-show");
           }

    $("#sidebar").removeClass("w3-hide");
    setCookie('sidebar_state', newstate, 30);
});

function toggleList(item) {
    var el_Button = document.getElementById(item);
    var sb_item = document.getElementById("sb_" + item);
    if (el_Button.classList.contains("collapsed") == true) {
        sb_item.classList.remove("w3-hide");
        el_Button.classList.remove("collapsed");
        setCookie("sb_"+ item, 'expanded', 30);
    } else { 
        sb_item.classList.add("w3-hide");     
        el_Button.classList.add("collapsed"); 
        setCookie("sb_"+ item, 'collapsed', 30);
    }
}

$(document).ready(function() {
//    $("#submit1").click(function()
//    {
//       $("#testForm").submit();	 
//    });
	var tab = getCookie("sidebar_tab");
	loadTab(tab);
});

function set_MenuItems() {
    // Get a string of all the cookies.
    var cookieArray = document.cookie.split(";");
    var result = new Array();
    // Create a key/value array with the individual cookies.
    for (var elem in cookieArray) {
        var temp = cookieArray[elem].split("=");
        // We need to trim whitespaces.
        temp[0] = $.trim(temp[0]);
        temp[1] = $.trim(temp[1]);
        // Only take sb_* cookies (= sidebar cookies)
        if (temp[0].substring(0, 3) == "sb_") {
            result[temp[0].substring(3)] = temp[1];
        }
    }
    // Finds the elements and if the cookie is collapsed, it
    // collapsed the found element.
    for (var key in result) {
        var x = document.getElementById(key);
        
        if ($("div#sb_" + key).length) {
            if (result[key] == "collapsed") {
                $("#sb_" + key).addClass("w3-hide");
            } else {
                $("#sb_" + key).removeClass("w3-hide");
            }
        }
    }
}

function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    var expires = "expires="+ d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function getCookie(cname) {
    var name = cname + "=";
    var decodedCookie = decodeURIComponent(document.cookie);
    var ca = decodedCookie.split(';');
    for(var i = 0; i <ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}

</script>
</div>

@endsection