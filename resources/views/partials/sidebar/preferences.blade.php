
<div id="sidebar_preferences" class="w3-container">
	   <button id="interface" onclick="toggleList('interface')" style="height:22px;" class="w3-left-align w3-btn w3-block w3-border-0 w3-small
	  {{ isset($_COOKIE['sb_interface']) ?:'collapsed' }}">Interface<i class="fa fa-caret-down"></i></button>
	<div id="sb_interface" class="w3-animate-left">
  		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">Link 1</a>
  		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">Link 2</a>
  		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;"">Link 3</a>
	</div>
	<button id="options" onclick="toggleList('options')" style="height:22px;" class="w3-left-align w3-btn w3-block w3-border-0 w3-small
	{{ isset($_COOKIE['sb_options']) ? : 'collapsed' }}">Options<i class="fa fa-caret-down"></i></button>
	<div id="sb_options" class="w3-animate-left">
  		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">Link 1</a>
  		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">Link 2</a>
  		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">Link 3</a>
  	</div>
	<button id="playlist" onclick="toggleList('playlist')" style="height:22px;" class="w3-left-align w3-btn w3-block w3-border-0 w3-small
	{{ isset($_COOKIE['sb_playlist']) ? : 'collapsed' }}">Playlist<i class="fa fa-caret-down"></i></button>
	<div id="sb_playlist" class="w3-hide w3-animate-left">
  		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">Link 1</a>
  		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">Link 2</a>
  		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">Link 3</a>
  	</div>
	<button id="streaming" onclick="toggleList('streaming')" style="height:22px;" class="w3-left-align w3-btn w3-block w3-border-0 w3-small
	{{ isset($_COOKIE['sb_streaming']) ? : 'collapsed' }}">Streaming<i class="fa fa-caret-down"></i></button>
	<div id="sb_streaming" class="w3-hide w3-animate-zoom">
  		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">Link 1</a>
  		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">Link 2</a>
  		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">Link 3</a>
  	</div>
	<button id="account" onclick="toggleList('account')" style="height:22px;" class="w3-left-align w3-btn w3-block w3-border-0 w3-small
	{{ isset($_COOKIE['sb_account']) ? : 'collapsed' }}">Account<i class="fa fa-caret-down"></i></button>
	<div id="sb_account" class="w3-hide w3-animate-zoom">
  		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">Link 1</a>
  		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">Link 2</a>
  		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">Link 3</a>
  	</div>
</div>
