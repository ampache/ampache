<ul class="sb2" id="sb_home">
<li><h4 class="header"><span class="sidebar-header-title" title="{{ 'Browse Music' }}">{{ 'Music' }}
</span><img src="{{ asset('images/icon_all.png') }}" class="header-img 
 {{ $_COOKIE['sb_browse_music'] == 'collapsed' ? 'collapsed' : 'expanded' }}" id="browse_music"
  lt=" {{ 'Expand/Collapse' }}" title="{{ 'Expand/Collapse' }}" /></h4>
        
        @if (isset($_REQUEST['action']))
            $text    = $_REQUEST['action'] . '_ac';
            ${$text} = ' selected="selected"';
        @endif
        <ul id="sb_browse_music" class="sb3 {{ $_COOKIE['sb_browse_music'] == 'collapsed' ? 'w3-hide' : '' }}">
            @if (config('feature.label'))
              <li id="sb_home_browse_music_label"><a href="#">{{ 'Labels' }}</a></li>
            @endif
            
              <li id="sb_home_browse_music_tags"><a href="#">{{'Tag Cloud' }}</a></li>
              <li id="sb_home_browse_music_playlist"><a href="#">{{ 'Playlists' }}</a></li>
              <li id="sb_home_browse_music_smartPlaylist"><a href="#">{{ 'Smart Playlists' }}</a></li>
              @if (config('feature.channel'))
                <li id="sb_home_browse_music_channel"><a href="#">{{ 'Channels' }}</a></li>
              @endif
              @if (config('feature.broadcast'))
                <li id="sb_home_browse_music_broadcast"><a href="#">{{ 'Broadcasts' }}</a></li>
              @endif
              @if (config('feature.live_stream'))
                <li id="sb_home_browse_music_radioStation"><a href="#">{{ 'Radio Stations' }}</a></li>
              @endif
            @if (config('feature.podcast'))
                <li id="sb_home_browse_music_podcast"><a href="#">{{ 'Podcasts' }}</a></li>
            @endif
            @if (config('feature.allow_upload') && Auth::check())
                <li id="sb_home_browse_music_upload"><a href="#">{{ 'Upload' }}</a></li>
            @endif
        </ul>
    </li>
</ul>