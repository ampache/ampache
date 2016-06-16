<div id="sb_Subsearch">
    <form name="search" method="post" action="{{ url('search.php?type=song') }}" enctype="multipart/form-data" style="Display:inline">
        <input type="text" name="rule_1_input" id="searchString" placeholder="{{ T_('Search...') }}" />
        <input type="hidden" name="action" value="search" />
        <input type="hidden" name="rule_1_operator" value="0" />
        <input type="hidden" name="object_type" value="song" />
        <select name="rule_1" id="searchStringRule">
            <option value="anywhere">{{ T_('Anywhere') }}</option>
            <option value="title">{{ T_('Title') }}</option>
            <option value="album">{{ T_('Album') }}</option>
            <option value="artist">{{ T_('Artist') }}</option>
            <option value="playlist_name">{{ T_('Playlist') }}</option>
            <option value="tag">{{ T_('Tag') }}</option>
            @if (Config::get('feature.label'))
                <option value="label">{{ T_('Label') }}</option>
            @endif
            @if (Config::get('feature.wanted'))
                <option value="missing_artist">{{ T_('Missing Artist') }}</option>
            @endif
        </select>
        <input class="button" type="submit" value="{{ T_('Search') }}" id="searchBtn" />
        <a href="{{ url('search.php?type=song') }}" class="button" id="advSearchBtn">{{ T_('Advanced Search') }}</a>
    </form>
</div>