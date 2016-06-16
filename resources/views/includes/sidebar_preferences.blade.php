<ul class="sb2" id="sb_preferences">
  <li>
      <h4 class="header"><span class="sidebar-header-title" title="{{ T_('Preferences') }}">{{ T_('Preferences') }}</span>
          <img src="{!! url_icon('all') !!}" class="header-all {{ isset($_COOKIE['sb_preferences']) ? $_COOKIE['sb_preferences'] : 'expanded' }}" id="preferences" alt="{{ T_('Expand/Collapse') }}" title="{{ T_('Expand/Collapse') }}" />
      </h4>
        <ul class="sb3" id="sb_preferences_sections">
            <li id="sb_preferences_sections"><a href="{!! url('account/preferences') !!}">{{ T_('Preferences') }}</a></li>
            <li id="sb_preferences_sections_account"><a href="{!! url('user/' . Auth::user()->id . '/edit') !!}">{{ T_('Account') }}</a></li>
        </ul>
  </li>
</ul>
