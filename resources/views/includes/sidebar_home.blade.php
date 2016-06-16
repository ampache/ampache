<ul class="sb2" id="sb_home">
    <li>
        <h4 class="header">
            <span class="sidebar-header-title" title="{{ T_('Browse Music') }}">{{ T_('Music') }}</span>
            <img src="{{ url_icon('all') }}" class="header-img {{ isset($_COOKIE['sb_browse_music']) ? $_COOKIE['sb_browse_music'] : 'expanded' }}" id="browse_music" lt="{{ T_('Expand/Collapse') }}" title="{{ T_('Expand/Collapse') }}" />
        </h4>
        <ul class="sb3" id="sb_browse_music">
            <li id="sb_home_browse_music_songTitle"><a href="{{ url('song') }}">{{ T_('Song Titles') }}</a></li>
            <li id="sb_home_browse_music_album"><a href="{{ url('album') }}">{{ T_('Albums') }}</a></li>
            <li id="sb_home_browse_music_artist"><a href="{{ url('artist') }}">{{ T_('Artists') }}</a></li>
            @if (Config::get('feature.label'))
                <li id="sb_home_browse_music_label"><a href="{{ url('label') }}">{{ T_('Labels') }}</a></li>
            @endif
            <li id="sb_home_browse_music_tags"><a href="{{ url('song/tags') }}">{{ T_('Tag Cloud') }}</a></li>
            <li id="sb_home_browse_music_playlist"><a href="{{ url('playlist') }}">{{ T_('Playlists') }}</a></li>
            <li id="sb_home_browse_music_smartPlaylist"><a href="{{ url('smartplaylist') }}">{{ T_('Smart Playlists') }}</a></li>
            @if (Config::get('feature.channel'))
                <li id="sb_home_browse_music_channel"><a href="{{ url('channel') }}">{{ T_('Channels') }}</a></li>
            @endif
            @if (Config::get('feature.broadcast'))
                <li id="sb_home_browse_music_broadcast"><a href="{{ url('broadcast') }}">{{ T_('Broadcasts') }}</a></li>
            @endif
            @if (Config::get('feature.live_stream'))
                <li id="sb_home_browse_music_radioStation"><a href="{{ url('live_stream') }}">{{ T_('Radio Stations') }}</a></li>
            @endif
            @if (Config::get('feature.podcast'))
                <li id="sb_home_browse_music_podcast"><a href="{{ url('podcast') }}">{{ T_('Podcasts') }}</a></li>
            @endif
            @if (Config::get('feature.allow_upload') && Auth::check())
                <li id="sb_home_browse_music_upload"><a href="{{ url('upload') }}">{{ T_('Upload') }}</a></li>
            @endif
        </ul>
    </li>
    @if (Config::get('feature.allow_video'))
        <li><h4 class="header">
                <span class="sidebar-header-title">{{ T_('Video') }}</span>
                <img src="{{ url_icon('all') }}" class="header-img {{ isset($_COOKIE['sb_browse_video']) ? $_COOKIE['sb_browse_video'] : 'expanded' }}" id="browse_video" lt="{{ T_('Expand/Collapse') }}" title="{{ T_('Expand/Collapse') }}" />
            </h4>
            <ul class="sb3" id="sb_home_browse_video">
                <li id="sb_home_browse_video_clip"><a href="{{ url('clip') }}">{{ T_('Music Clips') }}</a></li>
                <li id="sb_home_browse_video_tvShow"><a href="{{ url('tvshow') }}">{{ T_('TV Shows') }}</a></li>
                <li id="sb_home_browse_video_movie"><a href="{{ url('movie') }}">{{ T_('Movies') }}</a></li>
                <li id="sb_home_browse_video_video"><a href="{{ url('personal_video') }}">{{ T_('Personal Videos') }}</a></li>
                <li id="sb_home_browse_video_tagsVideo"><a href="{{ url('video/tags') }}">{{ T_('Tag Cloud') }}</a></li>
            </ul>
        </li>
    @endif
    <?php
    if (Config::get('theme.browse_filter')) {
        Ajax::start_container('browse_filters');
        Ajax::end_container();
    }
    ?>
    @if (Auth::check())
        <li>
            <h4 class="header">
                <span class="sidebar-header-title" title="{{ T_('Playlist') }}">{{ T_('Playlist') }}</span>
                <img src="{{ url_icon('all') }}" class="header-img {{ isset($_COOKIE['sb_home_playlist']) ? $_COOKIE['sb_home_playlist'] : 'expanded' }}" id="playlist" alt="{{ T_('Expand/Collapse') }}" title="{{ T_('Expand/Collapse') }}" />
            </h4>
            <ul class="sb3" id="sb_home_playlist">
                @if (Config::get('theme.home_now_playing'))
                    <li id="sb_home_playlist_currentlyPlaying"><a href="{{ url('/') }}">{{ T_('Currently Playing') }}</a></li>
                @endif
                @if (Config::get('feature.allow_democratic_playback'))
                    <li id="sb_home_playlist_playlist"><a href="{{ url('democratic') }}">{{ T_('Democratic') }}</a></li>
                @endif
                    <li id="sb_home_playlist_show"><a href="{{ url('localplay') }}">{{ T_('Localplay') }}</a></li>
                @if (Auth::user()->isContentManager())
                    <li id="sb_home_playlist_playlist"><a href="{{ url('playlist/import') }}">{{ T_('Import') }}</a></li>
                @endif
            </ul>
        </li>
    @endif
    <li>
        <h4 class="header">
            <span class="sidebar-header-title" title="{{ T_('Information') }}">{{ T_('Information') }}</span>
            <img src="{{ url_icon('all') }}" class="header-img {{ isset($_COOKIE['sb_info']) ? $_COOKIE['sb_info'] : 'expanded' }}" id="information" alt="{{ T_('Expand/Collapse') }}" title="{{ T_('Expand/Collapse') }}" />
        </h4>
        <ul class="sb3" id="sb_home_info">
            <li id="sb_home_info_recent"><a href="{{ url('stats/recent') }}">{{ T_('Recent') }}</a></li>
            <li id="sb_home_info_newest"><a href="{{ url('stats/newest') }}">{{ T_('Newest') }}</a></li>
            <li id="sb_home_info_popular"><a href="{{ url('stats/popular') }}">{{ T_('Popular') }}</a></li>
            @if (Auth::check())
                @if (Config::get('feature.ratings'))
                    <li id="sb_home_info_highest"><a href="{{ url('stats/highest') }}">{{ T_('Top Rated') }}</a></li>
                @endif
                @if (Config::get('feature.favorites'))
                    <li id="sb_home_info_userFlag"><a href="{{ url('stats/favorites') }}">{{ T_('Favorites') }}</a></li>
                @endif
                @if (Config::get('feature.wanted'))
                    <li id="sb_home_info_wanted"><a href="{{ url('stats/wanted') }}">{{ T_('Wanted List') }}</a></li>
                @endif
                @if (Config::get('feature.share'))
                    <li id="sb_home_info_share"><a href="{{ url('stats/share') }}">{{ T_('Shared Objects') }}</a></li>
                @endif
                @if (Config::get('feature.allow_upload'))
                    <li id="sb_home_info_upload"><a href="{{ url('stats/upload') }}">{{ T_('Uploads') }}</a></li>
                @endif
                @if (Auth::user()->isContentManager())
                    <li id="sb_home_info_statistic"><a href="{{ url('stats/metrics') }}">{{ T_('Statistics') }}</a></li>
                @endif
            @endif
        </ul>
    </li>
    <li>
        <h4 class="header">
            <span class="sidebar-header-title" title="{{ T_('Random') }}">{{ T_('Random') }}</span>
            <img src="{{ url_icon('all') }}" class="header-img {{ isset($_COOKIE['sb_random']) ? $_COOKIE['sb_random'] : 'collapsed' }}" id="random" alt="{{ T_('Expand/Collapse') }}" title="{{ T_('Expand/Collapse') }}" />
        </h4>
        <ul class="sb3" id="sb_home_random" style="{{ !isset($_COOKIE['sb_random']) ? 'display: none;' : '' }}">
            <li id="sb_home_random_album">{!! Ajax::text('?page=random&action=song', T_('Song'),'home_random_song') !!}</li>
            <li id="sb_home_random_album">{!! Ajax::text('?page=random&action=album', T_('Album'),'home_random_album') !!}</li>
            <li id="sb_home_random_artist">{!! Ajax::text('?page=random&action=artist', T_('Artist'),'home_random_artist') !!}</li>
            <li id="sb_home_random_playlist">{!! Ajax::text('?page=random&action=playlist', T_('Playlist'),'home_random_playlist') !!}</li>
            <li id="sb_home_random_advanced"><a href="{{ url('/random/song/?action=advanced') }}">{{ T_('Advanced') }}</a></li>
        </ul>
    </li>
    <li>
        <h4 class="header">
            <span class="sidebar-header-title" title="{{ T_('Search') }}">{{ T_('Search') }}</span>
            <img src="{{ url_icon('all') }}" class="header-img {{ isset($_COOKIE['sb_search']) ? $_COOKIE['sb_search'] : 'collapsed' }}" id="search" alt="{{ T_('Expand/Collapse') }}" title="{{ T_('Expand/Collapse') }}" />
        </h4>
        <ul class="sb3" id="sb_home_search" style="{{ (!isset($_COOKIE['sb_search']) ? 'display: none;' : '') }}">
          <li id="sb_home_search_song"><a href="{{ url('search/song') }}">{{ T_('Songs') }}</a></li>
          <li id="sb_home_search_album"><a href="{{ url('search/album') }}">{{ T_('Albums') }}</a></li>
          <li id="sb_home_search_artist"><a href="{{ url('search/artist') }}">{{ T_('Artists') }}</a></li>
          <li id="sb_home_search_playlist"><a href="{{ url('search/playlist') }}">{{ T_('Playlists') }}</a></li>
          @if (Config::get('feature.allow_video'))
                <li id="sb_home_search_video"><a href="{{ url('search/video') }}">{{ T_('Videos') }}</a></li>
          @endif
        </ul>
    </li>
</ul>