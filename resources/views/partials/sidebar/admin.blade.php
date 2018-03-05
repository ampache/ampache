
<div id="sidebar_admin" class="w3-container">
	<button id="catalogs" onclick="toggleList('catalogs')"style="height:22px;"class="w3-left-align w3-btn w3-block w3-border-0 w3-small
	{{ isset($_COOKIE['sb_catalogs']) ?:'collapsed' }}">Catalogs<i class="fa fa-caret-down"></i>
    </button>
	<div id="sb_catalogs" class="w3-animate-left">
  		<a href="{!! url('/catalogs/create') !!}" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">Add a Catalog</a>
  		<a href="{!! url('/catalogs') !!}"class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">Show Catalogs</a>
	</div>
	<button id="users" onclick="toggleList('users')"style="height:22px;"class="w3-left-align w3-btn w3-block w3-border-0 w3-small
	{{ isset($_COOKIE['sb_users']) ?:'collapsed' }}">Users<i class="fa fa-caret-down"></i>
	</button>
	<div id="sb_users" class="w3-animate-left">
  		<a href="{!! url('/users/create') !!}" rel="nohtml" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">Add User</a>
  		<a href="{!! url('/users') !!}" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">Browse Users</a>
  	</div>
	<button id="roles" onclick="toggleList('roles')"style="height:22px;"class="w3-left-align w3-btn w3-block w3-border-0 w3-small
	{{ isset($_COOKIE['sb_roles']) ?:'collapsed' }}">Roles and Permissions<i class="fa fa-caret-down"></i>
	</button>
	<div id="sb_roles" class="w3-animate-left">
  		<a href="{!! url('/roles/create') !!}" rel="nohtml" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">Add Role</a>
  		<a href="{!! url('/permissions/create') !!}" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">Browse Add Permission</a>
   		<a href="{!! url('/roles/index') !!}" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">Browse Roles</a>
	    <a href="{!! url('/permissions/index') !!}" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">Browse Permissions</a>
  	</div>
	<button id="access" onclick="toggleList('access')"style="height:22px;"class="w3-left-align w3-btn w3-block w3-border-0 w3-small
	{{ isset($_COOKIE['sb_access']) ?:'collapsed' }}">Access Control
	<i class="fa fa-caret-down"></i></button>
	<div id="sb_access" class="w3-animate-left">
  		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">Add ACL</a>
  		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">Show ACL(s)</a>
  	</div>
	<button id="other" onclick="toggleList('other')"style="height:22px;"class="w3-left-align w3-btn w3-block w3-border-0 w3-small
	{{ isset($_COOKIE['sb_other']) ?:'collapsed' }}">Other Tools<i class="fa fa-caret-down"></i></button>
	<div id="sb_other" class="w3-animate-left">
  		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">Ampache Debug</a>
  		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">Clear Now Playing</a>
   		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">Export Catalog</a>
  		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">Manage Shoutbox</a>
 		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">Manage Licenses</a>
  	</div>
	<button id="server" onclick="toggleList('server')"style="height:22px;"class="w3-left-align w3-btn w3-block w3-border-0 w3-small
	{{ isset($_COOKIE['sb_server']) ?:'collapsed' }}">Server Config
	<i class="fa fa-caret-down"></i></button>
	<div id="sb_server" class="w3-animate-left">
  		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">Interface</a>
  		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">Optionas</a>
   		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">Playlist</a>
  		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">Streaming</a>
 		<a href="#" class="w3-btn w3-block w3-left-align w3-tiny w3-text-gray" style="height:22px;">System</a>
  	</div>
</div>

<script>

</script>