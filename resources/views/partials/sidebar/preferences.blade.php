
<div id="sidebar_preferences">
	   <button id="preferences" onclick="toggleList('interface')" style="height:24px;font-size:12px;" class="w3-left-align w3-btn w3-block w3-border-0"
	  {{ isset($_COOKIE['sb_interface']) ?:'collapsed' }}">Preferences<i class="fa fa-caret-down"></i></button>
	<div id="sb_interface" class="w3-animate-left w3-text-sidebar w3-margin-left">
  		<a href="#" class="w3-btn w3-block w3-left-align" style="height:24px;font-size:12px;">Interface</a>
  		<a href="#" class="w3-btn w3-block w3-left-align" style="height:24px;font-size:12px;">Options</a>
  		<a href="#" class="w3-btn w3-block w3-left-align" style="height:24px;font-size:12px;"">Playlist</a>
  		<a href="#" class="w3-btn w3-block w3-left-align" style="height:24px;font-size:12px;">Streaming</a>
@if (Auth::check())
  		<a href="{!! url('/users/edit', Auth::id()) !!}" class="w3-btn w3-block w3-left-align" style="height:24px;font-size:12px;">Account</a>
@endif
	</div>
</div>
