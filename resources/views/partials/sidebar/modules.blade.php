<div id="sidebar_modules">
	<button id="modules" onclick="toggleList('modules')" style="height:24px;font-size:12px;"class="w3-left-align w3-btn w3-block w3-border-0"
	{{ isset($_COOKIE['sb_modules']) ?:'collapsed' }}">Modules
	<i class="fa fa-caret-down"></i></button>
		<div id="sb_modules" class="w3-animate-left w3-text-sidebar  w3-margin-left">
  		<a href="{!! url('/modules/show_catalogs') !!}" class="w3-btn w3-block w3-left-align w3-text-sidebar" style="height:24px;font-size:12px;">Catalog Modules</a>
  		<a href="{!! url('/modules/show_localplay') !!}" class="w3-btn w3-block w3-left-align w3-text-sidebar" style="height:24px;font-size:12px;">Localplay Modules</a>
  		<a href="{!! url('/modules/show_plugins') !!}" class="w3-btn w3-block w3-left-align w3-text-sidebar" style="height:24px;font-size:12px;">Available Plugins</a>
	</div>
	<button id="tools" onclick="toggleList('tools')" style="height:24px;font-size:12px;" class="w3-left-align w3-btn w3-block w3-border-0
	{{ isset($_COOKIE['sb_tools']) ? : 'collapsed' }}">Tools<i class="fa fa-caret-down"></i></button>
	<div id="sb_tools" class="w3-animate-left w3-text-sidebar  w3-margin-left">
  		<a href="#" class="w3-btn w3-block w3-left-align" style="height:24px;font-size:12px;">Find Duplicates</a>
  		<a href="#" class="w3-btn w3-block w3-left-align" style="height:24px;font-size:12px;">Mail User</a>
  	</div>
</div>
