<ul class="sb2" id="sb_admin">
    <li>
        <h4 class="header">
          <span class="sidebar-header-title" title="{{ T_('Catalogs') }}">{{ T_('Catalogs') }}</span>
          <img src="{!! url_icon('all') !!}" class="header-img {{ isset($_COOKIE['sb_catalogs']) ? $_COOKIE['sb_catalogs'] : 'expanded' }}" id="catalogs" alt="{{ T_('Expand/Collapse') }}" title="{{ T_('Expand/Collapse') }}" />
        </h4>
        <ul class="sb3" id="sb_admin_catalogs">
            <li id="sb_admin_catalogs_Add"><a href="{!! url('catalog/create') !!}">{{ T_('Add a Catalog') }}</a></li>
            <li id="sb_admin_catalogs_Show"><a href="{!! url('catalog') !!}">{{ T_('Show Catalogs') }}</a></li>
        </ul>
    </li>
    <li>
        <h4 class="header">
            <span class="sidebar-header-title" title="{{ T_('User Tools') }}">{{ T_('User Tools') }}</span>
            <img src="{!! url_icon('all') !!}" class="header-img {{ isset($_COOKIE['sb_user_tools']) ? $_COOKIE['sb_user_tools'] : 'expanded' }}" id="user_tools" alt="{{ T_('Expand/Collapse') }}" title="{{ T_('Expand/Collapse') }}" />
        </h4>
        <ul class="sb3" id="sb_admin_ut">
            <li id="sb_admin_ut_AddUser"><a href="{!! url('user/create') !!}">{{ T_('Add User') }}</a></li>
            <li id="sb_admin_ut_BrowseUsers"><a href="{!! url('user') !!}">{{ T_('Browse Users') }}</a></li>
        </ul>
    </li>
    {!! Ajax::start_container('browse_filters') !!}
    {!! Ajax::end_container() !!}
    <li>
        <h4 class="header">
            <span class="sidebar-header-title" title="{{ T_('Other Tools') }}">{{ T_('Other Tools') }}</span>
            <img src="{!! url_icon('all') !!}" class="header-img {{ isset($_COOKIE['sb_ad_other_tools']) ? $_COOKIE['sb_ad_other_tools'] : 'expanded' }}" id="ad_other_tools" alt="{{ T_('Expand/Collapse') }}" title="{{ T_('Expand/Collapse') }}" />
        </h4>
        <ul class="sb3" id="sb_admin_ot">
            <li id="sb_admin_ot_Debug"><a href="{!! url('system/debug') !!}">{{ T_('Ampache Debug') }}</a></li>
            <li id="sb_admin_ot_ClearNowPlaying"><a href="{!! url('system/clear') !!}">{{ T_('Clear Now Playing') }}</a></li>
            <li id="sb_admin_ot_ExportCatalog"><a href="{!! url('system/export') !!}">{{ T_('Export Catalog') }}</a></li>
            @if (Config::get('feature.sociable'))
            <li id="sb_admin_ot_ManageShoutbox"><a href="{!! url('shout') !!}">{{ T_('Manage Shoutbox') }}</a></li>
            @endif
            @if (Config::get('feature.licensing'))
            <li id="sb_admin_ot_ManageLicense"><a href="{!! url('license') !!}">{{ T_('Manage Licenses') }}</a></li>
            @endif
        </ul>
    </li>
    @if (Auth::check() && Auth::user()->isAdmin())
    <li>
        <h4 class="header">
            <span class="sidebar-header-title" title="{{ T_('Server Config') }}">{{ T_('Server Config') }}</span>
            <img src="{!! url_icon('all') !!}" class="header-img {{ isset($_COOKIE['sb_server_config']) ? $_COOKIE['sb_server_config'] : 'expanded' }}" id="server_config" alt="{{ T_('Expand/Collapse') }}" title="{{ T_('Expand/Collapse') }}" />
        </h4>
        <ul class="sb3" id="sb_preferences_sc">
            <li><a href="{!! url('preferences') !!}">{{ T_('Preferences') }}</a></li>
        </ul>
    </li>
    @endif
</ul>
