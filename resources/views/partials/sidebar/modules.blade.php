<div id="sidebar_modules" class="w3-container">
	<button id="modules" onclick="toggleList('modules')" class=" w3-left-align w3-btn w3-block w3-border-0 w3-small
	{{ isset($_COOKIE['sb_modules']) ?:'collapsed' }}">Modules
	<i class="fa fa-caret-down"></i></button>
		<div id="sb_modules" class="w3-animate-left">
  		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">Localplay Modules</a>
  		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">Catalog Modules</a>
  		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;"">Available Plugins</a>
	</div>
	<button id="tools" onclick="toggleList('tools')" style="height:22px;" class="w3-left-align w3-btn w3-block w3-border-0 w3-small
	{{ isset($_COOKIE['sb_tools']) ? : 'collapsed' }}">Tools<i class="fa fa-caret-down"></i></button>
	<div id="sb_tools" class="w3-animate-left">
  		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">Find Duplicates</a>
  		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">Mail User</a>
  	</div>
</div>
