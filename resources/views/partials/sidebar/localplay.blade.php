
<div id="sidebar_localplay" class="w3-container">
	<button id="localplay" onclick="toggleList('localplay')" style="height:22px;" class=" w3-left-align w3-btn w3-block w3-border-0 w3-small
	 {{ isset($_COOKIE['sb_localplay']) ?:'collapsed' }}">Localplay
	<i class="fa fa-caret-down"></i></button>
	<div id="sb_localplay" class="w3-animate-left ">
  		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">add Instance</a>
  		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;"">Show Instances</a>
  		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;"">Show Playlists</a>
	</div>
	<button id="instance" onclick="toggleList('instance')" style="height:22px;" class=" w3-left-align w3-btn w3-block w3-border-0 w3-small
	 {{ isset($_COOKIE['sb_instance']) ?:'collapsed' }}">Active Instance
	<i class="fa fa-caret-down"></i></button>
	<div id="sb_instance" class="w3-animate-left ">
  		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">none</a>
  		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;"">Show Instances</a>
  		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;"">Show Playlists</a>
	</div>
</div>
