<ul class="sb2" id="sb_modules">
<li>
    <h4 class="header">
        <span class="sidebar-header-title" title="{{ T_('Modules') }}">{{ T_('Modules') }}</span>
        <img src="{!! url_icon('all') !!}" class="header-img {{ isset($_COOKIE['sb_modules']) ? $_COOKIE['sb_modules'] : 'expanded' }}" id="modules" alt="{{ T_('Expand/Collapse') }}" title="{{ T_('Expand/Collapse') }}" />
    </h4>
    <ul class="sb3" id="sb_Modules">
        <li id="sb_preferences_mo_localplay"><a href="{!! url('modules/localplay') !!}">{{ T_('Localplay Modules') }}</a></li>
        <li id="sb_preferences_mo_catalog_types"><a href="{!! url('modules/catalog') !!}">{{ T_('Catalog Modules') }}</a></li>
        <li id="sb_preferences_mo_plugins"><a href="{!! url('modules/plugin') !}}">{{ T_('Available Plugins') }}</a></li>
    </ul>
</li>
  <li><h4 class="header">
          <span class="sidebar-header-title" title="{{ T_('Other Tools') }}">{{ T_('Other Tools') }}</span>
          <img src="{!! url_icon('all') !!}" class="header-img {{ isset($_COOKIE['sb_md_other_tools']) ? $_COOKIE['sb_md_other_tools'] : 'expanded' }}" id="md_other_tools" alt="{{ T_('Expand/Collapse') }}" title="{{ T_('Expand/Collapse') }}" />
      </h4>
    <ul class="sb3" id="sb_admin_ot">
      <li id="sb_admin_ot_Duplicates"><a href="{!! url('duplicates') }}">{{ T_('Find Duplicates') }}</a></li>
      <li id="sb_admin_ot_Mail"><a href="{!! url('mail/all') !}}">{{ T_('Mail Users') }}</a></li>
    </ul>
  </li>
<!--
@if (Config::get('feature.allow_democratic_playback'))
  <li><h4>{{ T_('Democratic') }}</h4>
    <ul class="sb3" id="sb_home_democratic">
      <li id="sb_home_democratic_playlist"><a href="{!! url('democratic/manage') !!}">{{ T_('Manage Playlist') }}</a></li>
    </ul>
  </li>
@endif
-->
</ul>
