<ul class="sb2" id="sb_localplay">
    @if (Config::get('feature.allow_localplay_playback'))
        @if (Auth::check() && Auth::user()->isRegisteredUser())
        <li>
            <h4 class="header">
                <span class="sidebar-header-title" title="{{ T_('Localplay') }}">{{ T_('Localplay') }}</span>
                <img src="{!! url_icon('all') !!}" class="header-img {{ isset($_COOKIE['sb_localplay']) ? $_COOKIE['sb_localplay'] : 'expanded' }}" id="localplay" alt="{{ T_('Expand/Collapse') }}" title="{{ T_('Expand/Collapse') }}" />
            </h4>
            <ul class="sb3" id="sb_localplay_info">
                @if (Auth::user()->isCatalogManager())
                    <li id="sb_localplay_info_add_instance"><a href="{!! url('localplay/create') !!}">{{ T_('Add Instance') }}</a></li>
                    <li id="sb_localplay_info_show_instances"><a href="{!! url('localplay') !!}">{{ T_('Show instances') }}</a></li>
                @endif
                <li id="sb_localplay_info_show"><a href="{!! url('localplay/playlist') !!}">{{ T_('Show Playlist') }}</a></li>
            </ul>
        </li>
        @endif
        <li>
            <h4 class="header">
                <span class="sidebar-header-title" title="{{ T_('Active Instance') }}">{{ T_('Active Instance') }}</span>
                <img src="{!! url_icon('all') !!}" class="header-img {{ isset($_COOKIE['sb_active_instance']) ? $_COOKIE['sb_active_instance'] : 'expanded' }}" id="active_instance" alt="{{ T_('Expand/Collapse') }}" title="{{ T_('Expand/Collapse') }}" />
            </h4>
            <ul class="sb3" id="sb_localplay_instances">
                <li id="sb_localplay_instances_none" class="{{ (Localplay::getCurrentInstance() !== null) ? 'active_instance' : '' }}}">{!! Ajax::text('?page=localplay&action=set_instance&instance=0', T_('None'),'localplay_instance_none') !!}</li>
                @foreach (Localplay::getInstances() as $uid => $name)
                    <li id="sb_localplay_instances_{{ $uid }}" class="{{ ($uid === Localplay::getCurrentInstance()) ? 'active_instance' : '' }}}">{!! Ajax::text('?page=localplay&action=set_instance&instance=' . $uid, $name, 'localplay_instance_' . $uid) !!}</li>
                @endforeach
            </ul>
        </li>
    @else
    <li>
        <h4 class="header">
            <span class="sidebar-header-title" title="{{ T_('Localplay Disabled') }}">{{ T_('Localplay Disabled') }}</span>
            <img src="{!! url_icon('all') !!}" class="header-img {{ isset($_COOKIE['sb_localplay_disabled']) ? $_COOKIE['sb_localplay_disabled'] : 'expanded' }}" id="localplay_disabled" alt="{{ T_('Expand/Collapse') }}" title="{{ T_('Expand/Collapse') }}" />
        </h4>
    </li>
    @endif
</ul>
